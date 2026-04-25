<?php

namespace App\Services;

use App\Models\Transaction;
use Carbon\Carbon;

class AnomalyDetectionService
{
    /**
     * Ensemble Anomaly Detection
     * Combines Z-Score (0.35), IQR (0.35), and Moving Average (0.30)
     * Runs per-category to establish context-sensitive baselines
     */
    public function detect(float $amount, array $historicalAmounts, int $windowSize = 30): array
    {
        if (count($historicalAmounts) < 5) {
            return [
                'is_anomaly' => false,
                'score' => 0,
                'severity' => null,
                'reasons' => ['Data historis belum cukup untuk analisis (minimal 5 transaksi)'],
                'details' => [
                    'zscore' => ['value' => 0, 'threshold' => 2.5, 'flagged' => false],
                    'iqr' => ['lower_bound' => 0, 'upper_bound' => 0, 'flagged' => false],
                    'moving_avg' => ['expected' => 0, 'deviation' => 0, 'flagged' => false],
                ],
            ];
        }

        $reasons = [];

        // Metode 1: Z-Score
        $mean = $this->avg($historicalAmounts);
        $stdDev = $this->standardDeviation($historicalAmounts);
        $zScore = $stdDev > 0 ? ($amount - $mean) / $stdDev : 0;
        $zThreshold = 2.5;
        $zFlagged = abs($zScore) > $zThreshold;
        if ($zFlagged) {
            $reasons[] = sprintf(
                'Jumlah %.1fσ dari rata-rata (%s) — sangat menyimpang dari pola normal',
                abs($zScore),
                $this->formatRp($mean)
            );
        }

        // Metode 2: IQR (Interquartile Range)
        $sorted = $historicalAmounts;
        sort($sorted);
        $q1 = $this->percentile($sorted, 25);
        $q3 = $this->percentile($sorted, 75);
        $iqr = $q3 - $q1;
        $lowerBound = $q1 - 1.5 * $iqr;
        $upperBound = $q3 + 1.5 * $iqr;
        $iqrFlagged = $amount < $lowerBound || $amount > $upperBound;
        if ($iqrFlagged) {
            $reasons[] = sprintf(
                'Jumlah di luar rentang wajar [%s – %s]',
                $this->formatRp(max(0, $lowerBound)),
                $this->formatRp($upperBound)
            );
        }

        // Metode 3: Deviasi Rata-rata Bergerak
        $recentWindow = array_slice($historicalAmounts, -$windowSize);
        $movingMean = $this->avg($recentWindow);
        $deviation = $movingMean > 0 ? abs($amount - $movingMean) / $movingMean : 0;
        $maThreshold = 0.5;
        $maFlagged = $deviation > $maThreshold;
        if ($maFlagged) {
            $reasons[] = sprintf(
                'Deviasi %d%% dari rata-rata %d hari terakhir (%s)',
                round($deviation * 100),
                $windowSize,
                $this->formatRp($movingMean)
            );
        }

        // Skor ensemble
        $score = ($zFlagged ? 0.35 : 0) + ($iqrFlagged ? 0.35 : 0) + ($maFlagged ? 0.30 : 0);

        $severity = null;
        if ($score > 0.7) $severity = 'HIGH';
        elseif ($score > 0.4) $severity = 'MEDIUM';
        elseif ($score > 0.2) $severity = 'LOW';

        return [
            'is_anomaly' => $score > 0.2,
            'score' => $score,
            'severity' => $severity,
            'reasons' => $reasons,
            'details' => [
                'zscore' => ['value' => $zScore, 'threshold' => $zThreshold, 'flagged' => $zFlagged],
                'iqr' => ['lower_bound' => max(0, $lowerBound), 'upper_bound' => $upperBound, 'flagged' => $iqrFlagged],
                'moving_avg' => ['expected' => $movingMean, 'deviation' => $deviation, 'flagged' => $maFlagged],
            ],
        ];
    }

    private function avg(array $arr): float
    {
        return count($arr) > 0 ? array_sum($arr) / count($arr) : 0;
    }

    private function standardDeviation(array $arr): float
    {
        $mean = $this->avg($arr);
        $variance = array_sum(array_map(fn($v) => pow($v - $mean, 2), $arr)) / count($arr);
        return sqrt($variance);
    }

    private function percentile(array $sorted, float $p): float
    {
        $index = ($p / 100) * (count($sorted) - 1);
        $lower = (int) floor($index);
        $upper = (int) ceil($index);
        if ($lower === $upper) return $sorted[$lower];
        return $sorted[$lower] + ($sorted[$upper] - $sorted[$lower]) * ($index - $lower);
    }

    private function formatRp(float $amount): string
    {
        return 'Rp ' . number_format(round($amount), 0, ',', '.');
    }

    /**
     * Month-over-Month Comparative Variance Detection.
     * Compares category-level spending between two consecutive months.
     * Flags categories where spending spikes > threshold (default 150%).
     */
    public function detectMoMVariance(
        Carbon $currentMonth,
        ?int $accountId = null,
        float $threshold = 1.5
    ): array {
        $currStart = $currentMonth->copy()->startOfMonth();
        $currEnd = $currentMonth->copy()->endOfMonth();
        $prevStart = $currentMonth->copy()->subMonth()->startOfMonth();
        $prevEnd = $currentMonth->copy()->subMonth()->endOfMonth();

        // Get category totals for current month
        $currQuery = Transaction::credit()
            ->whereBetween('transaction_date', [$currStart, $currEnd])
            ->whereNotNull('category_id');
        if ($accountId) $currQuery->where('bank_account_id', $accountId);

        $currTotals = $currQuery->selectRaw('category_id, SUM(amount) as total, COUNT(*) as tx_count')
            ->groupBy('category_id')
            ->pluck('total', 'category_id')
            ->toArray();

        // Get category totals for previous month
        $prevQuery = Transaction::credit()
            ->whereBetween('transaction_date', [$prevStart, $prevEnd])
            ->whereNotNull('category_id');
        if ($accountId) $prevQuery->where('bank_account_id', $accountId);

        $prevTotals = $prevQuery->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id')
            ->toArray();

        $variances = [];
        $categories = \App\Models\Category::whereIn('id', array_unique(array_merge(
            array_keys($currTotals), array_keys($prevTotals)
        )))->pluck('name', 'id');

        foreach ($currTotals as $catId => $currAmount) {
            $prevAmount = $prevTotals[$catId] ?? 0;

            if ($prevAmount <= 0) {
                // New category spending — flag if significant
                if ($currAmount >= 500000) { // > Rp 500k
                    $variances[] = [
                        'category_id' => $catId,
                        'category_name' => $categories[$catId] ?? 'Unknown',
                        'prev_amount' => 0,
                        'curr_amount' => round($currAmount),
                        'variance_pct' => 999,
                        'severity' => 'HIGH',
                        'reason' => sprintf(
                            'Kategori baru muncul di %s dengan total %s (tidak ada di bulan sebelumnya)',
                            $currStart->translatedFormat('F Y'),
                            $this->formatRp($currAmount)
                        ),
                    ];
                }
                continue;
            }

            $variance = ($currAmount - $prevAmount) / $prevAmount;

            if ($variance > $threshold) {
                $severity = $variance > 3.0 ? 'HIGH' : ($variance > 2.0 ? 'MEDIUM' : 'LOW');
                $variances[] = [
                    'category_id' => $catId,
                    'category_name' => $categories[$catId] ?? 'Unknown',
                    'prev_amount' => round($prevAmount),
                    'curr_amount' => round($currAmount),
                    'variance_pct' => round($variance * 100),
                    'severity' => $severity,
                    'reason' => sprintf(
                        '%s naik %d%% dari %s → %s (%s vs %s)',
                        $categories[$catId] ?? 'Kategori',
                        round($variance * 100),
                        $prevStart->translatedFormat('F'),
                        $currStart->translatedFormat('F'),
                        $this->formatRp($prevAmount),
                        $this->formatRp($currAmount)
                    ),
                ];
            }
        }

        // Sort by variance descending
        usort($variances, fn($a, $b) => $b['variance_pct'] <=> $a['variance_pct']);

        return $variances;
    }
}
