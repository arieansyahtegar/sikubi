<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class AnomalyDetectionService
{
    /**
     * Income Anomaly Detection (Pemasukan)
     *
     * Detects accounts that sent >= 10 million IDR to Bigenmi,
     * either in a single transaction or accumulated across multiple transactions.
     * Groups by sender description keyword to identify unique senders.
     */
    public function detectIncomeAnomalies(?int $bankAccountId = null): array
    {
        $threshold = 10_000_000; // Rp 10 juta

        $query = Transaction::debit(); // DEBIT = money coming IN to Bigenmi
        if ($bankAccountId) {
            $query->where('bank_account_id', $bankAccountId);
        }

        // Group transactions by description to identify senders
        $transactions = $query->with('bankAccount:id,bank_name,account_alias')
            ->orderBy('transaction_date')
            ->get();

        $anomalies = [];

        // Strategy 1: Single transactions >= 10 juta (instant)
        foreach ($transactions as $tx) {
            if ((float) $tx->amount >= $threshold) {
                $anomalies[] = [
                    'type' => 'INCOME',
                    'subtype' => 'INSTANT',
                    'transaction_id' => $tx->id,
                    'severity' => (float) $tx->amount >= 50_000_000 ? 'HIGH' : 'MEDIUM',
                    'score' => min(1.0, (float) $tx->amount / 50_000_000),
                    'amount' => (float) $tx->amount,
                    'reason' => sprintf(
                        'Pemasukan instan %s dalam satu transaksi — melebihi batas Rp 10 juta. Perlu verifikasi sumber dana.',
                        $this->formatRp($tx->amount)
                    ),
                ];
            }
        }

        // Strategy 2: Accumulated from same sender >= 10 juta
        // Group by normalized description (first meaningful keyword)
        $senderGroups = $transactions->groupBy(function ($tx) {
            return $this->normalizeSender($tx->description);
        });

        foreach ($senderGroups as $sender => $txGroup) {
            if ($sender === 'LAINNYA') continue; // Skip generic

            $totalAmount = $txGroup->sum(fn($tx) => (float) $tx->amount);

            if ($totalAmount >= $threshold && $txGroup->count() > 1) {
                // Only flag if NOT already flagged as instant (avoid duplicates)
                $hasInstant = $txGroup->contains(fn($tx) => (float) $tx->amount >= $threshold);
                if ($hasInstant && $txGroup->count() <= 1) continue;

                $representativeTx = $txGroup->sortByDesc('amount')->first();

                $anomalies[] = [
                    'type' => 'INCOME',
                    'subtype' => 'ACCUMULATED',
                    'transaction_id' => $representativeTx->id,
                    'severity' => $totalAmount >= 50_000_000 ? 'HIGH' : 'MEDIUM',
                    'score' => min(1.0, $totalAmount / 50_000_000),
                    'amount' => $totalAmount,
                    'reason' => sprintf(
                        'Akumulasi pemasukan dari "%s" sebesar %s (%d transaksi) — melebihi batas Rp 10 juta. Perlu tinjauan.',
                        $sender,
                        $this->formatRp($totalAmount),
                        $txGroup->count()
                    ),
                ];
            }
        }

        return $anomalies;
    }

    /**
     * Expense Anomaly Detection (Pengeluaran)
     *
     * Detects outgoing transactions to accounts where the total outgoing
     * exceeds the total incoming from that same account.
     * This flags imbalanced relationships (paying more than received).
     */
    public function detectExpenseAnomalies(?int $bankAccountId = null): array
    {
        // Get all transactions grouped by normalized counterparty
        $debitQuery = Transaction::debit(); // Incoming
        $creditQuery = Transaction::credit(); // Outgoing

        if ($bankAccountId) {
            $debitQuery->where('bank_account_id', $bankAccountId);
            $creditQuery->where('bank_account_id', $bankAccountId);
        }

        $incoming = $debitQuery->get();
        $outgoing = $creditQuery->with('bankAccount:id,bank_name,account_alias')->get();

        // Build incoming totals by counterparty
        $incomingByParty = [];
        foreach ($incoming as $tx) {
            $party = $this->normalizeSender($tx->description);
            if ($party === 'LAINNYA') continue;
            $incomingByParty[$party] = ($incomingByParty[$party] ?? 0) + (float) $tx->amount;
        }

        // Build outgoing totals by counterparty
        $outgoingByParty = [];
        $outgoingTxByParty = [];
        foreach ($outgoing as $tx) {
            $party = $this->normalizeSender($tx->description);
            if ($party === 'LAINNYA') continue;
            $outgoingByParty[$party] = ($outgoingByParty[$party] ?? 0) + (float) $tx->amount;
            if (!isset($outgoingTxByParty[$party])) {
                $outgoingTxByParty[$party] = collect();
            }
            $outgoingTxByParty[$party]->push($tx);
        }

        $anomalies = [];

        foreach ($outgoingByParty as $party => $totalOut) {
            $totalIn = $incomingByParty[$party] ?? 0;

            // Flag if outgoing exceeds incoming (paying more than received)
            if ($totalOut > $totalIn && $totalOut >= 1_000_000) {
                $excess = $totalOut - $totalIn;
                $representativeTx = $outgoingTxByParty[$party]->sortByDesc('amount')->first();

                $severity = 'MEDIUM';
                if ($excess >= 50_000_000) $severity = 'HIGH';
                elseif ($totalIn == 0 && $totalOut >= 10_000_000) $severity = 'HIGH';

                $reason = $totalIn > 0
                    ? sprintf(
                        'Pengeluaran ke "%s" sebesar %s melebihi pemasukan dari akun tersebut (%s). Selisih: %s.',
                        $party,
                        $this->formatRp($totalOut),
                        $this->formatRp($totalIn),
                        $this->formatRp($excess)
                    )
                    : sprintf(
                        'Pengeluaran ke "%s" sebesar %s tanpa ada pemasukan dari akun tersebut. Perlu verifikasi tujuan.',
                        $party,
                        $this->formatRp($totalOut)
                    );

                $anomalies[] = [
                    'type' => 'EXPENSE',
                    'subtype' => 'MISMATCH',
                    'transaction_id' => $representativeTx->id,
                    'severity' => $severity,
                    'score' => $totalIn > 0 ? min(1.0, $excess / $totalOut) : 1.0,
                    'amount' => $totalOut,
                    'reason' => $reason,
                ];
            }
        }

        return $anomalies;
    }

    /**
     * Run full anomaly detection (both income and expense).
     */
    public function runFullDetection(?int $bankAccountId = null): array
    {
        $income = $this->detectIncomeAnomalies($bankAccountId);
        $expense = $this->detectExpenseAnomalies($bankAccountId);

        return array_merge($income, $expense);
    }

    /**
     * Normalize transaction description to extract sender/counterparty name.
     * Strips common prefixes like "TRSF", "TRANSFER", "BCA", etc.
     */
    private function normalizeSender(string $description): string
    {
        $desc = strtoupper(trim($description));

        // Remove common bank transaction prefixes
        $prefixes = [
            'TRSF E-BANKING DB', 'TRSF E-BANKING CR', 'TRSF E-BANKING',
            'SWITCHING DB', 'SWITCHING CR', 'SWITCHING',
            'TARIKAN ATM', 'SETORAN TUNAI',
            'TRANSFER DR', 'TRANSFER CR', 'TRANSFER',
            'TRSF DB', 'TRSF CR', 'TRSF',
            'KR OTOMATIS', 'DB OTOMATIS',
            'BIAYA ADM', 'BUNGA',
            'FLEKSI BCA', 'BCA',
        ];

        foreach ($prefixes as $prefix) {
            if (str_starts_with($desc, $prefix)) {
                $desc = trim(substr($desc, strlen($prefix)));
                break;
            }
        }

        // Remove date patterns (DD/MM, DD-MM-YYYY, etc.)
        $desc = preg_replace('/\d{2}[\/\-]\d{2}([\/\-]\d{2,4})?/', '', $desc);

        // Remove reference numbers (long digit sequences)
        $desc = preg_replace('/\b\d{6,}\b/', '', $desc);

        // Clean up
        $desc = preg_replace('/\s+/', ' ', trim($desc));

        // Extract first meaningful part (usually the name)
        $parts = explode(' ', $desc);
        $name = implode(' ', array_slice($parts, 0, min(3, count($parts))));

        return $name ?: 'LAINNYA';
    }

    private function formatRp(float $amount): string
    {
        return 'Rp ' . number_format(round($amount), 0, ',', '.');
    }
}
