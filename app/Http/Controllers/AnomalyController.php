<?php

namespace App\Http\Controllers;

use App\Models\AnomalyFlag;
use App\Models\Transaction;
use App\Services\AnomalyDetectionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnomalyController extends Controller
{
    public function index(Request $request)
    {
        $severity = $request->input('severity', 'HIGH');

        $query = AnomalyFlag::with([
            'transaction' => fn($q) => $q->with('category:id,name,color', 'bankAccount:id,bank_name,account_alias'),
        ])->orderByDesc('detected_at');

        if ($severity !== 'ALL') {
            $query->where('severity', $severity);
        }

        $anomalies = $query->paginate(20)->withQueryString();

        return Inertia::render('Anomalies', [
            'anomalies' => $anomalies,
            'filters' => [
                'severity' => $severity,
            ]
        ]);
    }

    public function detect(Request $request, AnomalyDetectionService $detector)
    {
        $accountId = $request->input('account_id');
        $days = $request->input('days', 90);
        $startDate = now()->subDays($days);

        $query = Transaction::credit()
            ->where('transaction_date', '>=', $startDate);
        if ($accountId) $query->where('bank_account_id', $accountId);

        $transactions = $query->with('category')->orderBy('transaction_date')->get();

        // Group by category
        $categoryGroups = $transactions->groupBy(fn($tx) => $tx->category_id ?? 'uncategorized');

        // Clear unreviewed flags
        AnomalyFlag::where('is_reviewed', false)
            ->where('is_dismissed', false)
            ->delete();

        $flagsCreated = 0;

        foreach ($categoryGroups as $catId => $txs) {
            if ($txs->count() < 5) continue;

            $amounts = $txs->pluck('amount')->map(fn($a) => (float) $a)->toArray();

            foreach ($txs as $tx) {
                $historical = array_values(array_filter(
                    $amounts,
                    fn($_, $i) => $txs->values()[$i]->id !== $tx->id,
                    ARRAY_FILTER_USE_BOTH
                ));

                $result = $detector->detect((float) $tx->amount, $historical);

                if ($result['is_anomaly'] && $result['severity']) {
                    AnomalyFlag::create([
                        'transaction_id' => $tx->id,
                        'detection_method' => 'ENSEMBLE',
                        'score' => $result['score'],
                        'severity' => $result['severity'],
                        'reason' => implode('; ', $result['reasons']),
                    ]);
                    $flagsCreated++;
                }
            }
        }

        return back()->with('detectResult', [
            'message' => "Anomaly detection complete. Found {$flagsCreated} anomalies.",
            'flags_created' => $flagsCreated,
            'transactions_scanned' => $transactions->count(),
        ]);
    }

    /**
     * Month-over-Month Comparative Variance Detection
     */
    public function detectMoM(Request $request, AnomalyDetectionService $detector)
    {
        $month = $request->input('month', now()->format('Y-m'));
        $accountId = $request->input('account_id');

        $currentMonth = Carbon::createFromFormat('Y-m', $month);
        $variances = $detector->detectMoMVariance($currentMonth, $accountId);

        // Create anomaly flags for significant variances
        $flagsCreated = 0;
        foreach ($variances as $v) {
            if ($v['severity'] === 'HIGH' || $v['severity'] === 'MEDIUM') {
                // Find a representative transaction from this category this month
                $tx = Transaction::credit()
                    ->where('category_id', $v['category_id'])
                    ->whereBetween('transaction_date', [
                        $currentMonth->copy()->startOfMonth(),
                        $currentMonth->copy()->endOfMonth(),
                    ])
                    ->orderByDesc('amount')
                    ->first();

                if ($tx && !AnomalyFlag::where('transaction_id', $tx->id)->where('detection_method', 'MOM_VARIANCE')->exists()) {
                    AnomalyFlag::create([
                        'transaction_id' => $tx->id,
                        'detection_method' => 'MOM_VARIANCE',
                        'score' => min($v['variance_pct'] / 100, 1.0),
                        'severity' => $v['severity'],
                        'reason' => $v['reason'],
                    ]);
                    $flagsCreated++;
                }
            }
        }

        return back()->with('momResult', [
            'message' => "Analisis MoM selesai. {$flagsCreated} anomali ditemukan.",
            'variances' => $variances,
            'flags_created' => $flagsCreated,
            'month' => $currentMonth->translatedFormat('F Y'),
        ]);
    }

    public function review(Request $request, $id)
    {
        $flag = AnomalyFlag::findOrFail($id);
        $flag->update([
            'is_reviewed' => true,
            'is_dismissed' => $request->boolean('dismiss', false),
        ]);
        return back();
    }
}
