<?php

namespace App\Http\Middleware;

use App\Models\AnomalyFlag;
use App\Models\ImportBatch;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
            ],
            'permissions' => [
                'canImport' => $user?->isAdmin() ?? false,
                'canManageAccounts' => $user?->isAdmin() ?? false,
                'canManageSettings' => $user?->isAdmin() ?? false,
                'canDetectAnomalies' => $user?->isAdmin() ?? false,
                'canManageUsers' => $user?->isDirektur() ?? false,
                'canEditTransactions' => $user?->isAdmin() ?? false,
                'canManageCashTransactions' => $user?->isAdmin() ?? false,
            ],
            'notifications' => fn () => $user ? $this->getNotifications($user) : ['items' => [], 'unread_count' => 0],
            'flash' => [
                'importResult' => fn () => $request->session()->get('importResult'),
                'detectResult' => fn () => $request->session()->get('detectResult'),
            ],
        ];
    }

    private function getNotifications($user): array
    {
        $items = [];

        if ($user->isAdmin()) {
            // 1. Unreviewed anomalies (ordered by severity priority: HIGH first, then date)
            $unreviewedAnomalies = AnomalyFlag::where('is_reviewed', false)
                ->where('is_dismissed', false)
                ->orderByRaw("CASE severity WHEN 'HIGH' THEN 1 WHEN 'MEDIUM' THEN 2 WHEN 'LOW' THEN 3 ELSE 4 END")
                ->orderByDesc('detected_at')
                ->limit(5)
                ->with(['transaction:id,description,amount,type'])
                ->get();

            foreach ($unreviewedAnomalies as $flag) {
                $desc = mb_substr($flag->transaction?->description ?? '', 0, 30);
                $amt = number_format($flag->transaction?->amount ?? 0, 0, ',', '.');
                $items[] = [
                    'id' => 'anomaly_' . $flag->id,
                    'type' => 'anomaly',
                    'severity' => $flag->severity,
                    'title' => $flag->severity === 'HIGH' ? 'Anomali Kritis Terdeteksi' : 'Anomali Terdeteksi',
                    'message' => "Transaksi '{$desc}...' bernilai Rp {$amt} terdeteksi sebagai anomali.",
                    'url' => '/anomalies?flag_id=' . $flag->id,
                    'time' => $flag->detected_at?->toISOString(),
                    'read' => false,
                ];
            }

            // 2. Recent imports (last 24h) - warns if duplicates detected and STILL pending
            $recentImports = ImportBatch::where('created_at', '>=', now()->subDay())
                ->orderByDesc('created_at')
                ->limit(3)
                ->get();

            foreach ($recentImports as $batch) {
                $hasPendingDuplicates = \App\Models\DuplicateTransaction::where('import_batch_id', $batch->id)
                    ->where('status', 'PENDING')
                    ->exists();
                $items[] = [
                    'id' => 'import_' . $batch->id,
                    'type' => 'import',
                    'severity' => $hasPendingDuplicates ? 'WARNING' : 'INFO',
                    'title' => $hasPendingDuplicates ? 'Import Selesai dengan Duplikat' : 'Import Data Selesai',
                    'message' => $hasPendingDuplicates 
                        ? "{$batch->success_rows} sukses, {$batch->duplicate_rows} duplikat memerlukan tindakan." 
                        : "{$batch->success_rows} transaksi berhasil diimport.",
                    'url' => '/import',
                    'time' => $batch->created_at?->toISOString(),
                    'read' => !$hasPendingDuplicates, // keeps unread if warning
                ];
            }

            // 3. Escalated anomalies decided by Pimpinan (last 7 days)
            $decidedAnomalies = AnomalyFlag::where('is_reviewed', true)
                ->where('needs_leader_action', true)
                ->whereNotNull('leader_reviewed_at')
                ->where('is_dismissed', false)
                ->where('leader_reviewed_at', '>=', now()->subDays(7))
                ->orderByDesc('leader_reviewed_at')
                ->limit(5)
                ->with(['transaction:id,description,amount,type'])
                ->get();

            foreach ($decidedAnomalies as $flag) {
                $status = $flag->is_approved_by_leader ? 'Disetujui' : 'Ditolak';
                $desc = mb_substr($flag->transaction?->description ?? '', 0, 30);
                $amt = number_format($flag->transaction?->amount ?? 0, 0, ',', '.');
                $items[] = [
                    'id' => 'decided_anomaly_' . $flag->id,
                    'type' => 'anomaly_decided',
                    'severity' => $flag->is_approved_by_leader ? 'INFO' : 'WARNING',
                    'title' => "Otorisasi Pimpinan: {$status}",
                    'message' => "Transaksi '{$desc}...' bernilai Rp {$amt} {$status} oleh Pimpinan.",
                    'url' => '/anomalies?flag_id=' . $flag->id,
                    'time' => $flag->leader_reviewed_at?->toISOString(),
                    'read' => false,
                ];
            }
        }

        if ($user->isDirektur()) {
            // 1. Anomalies needing leader action, not yet reviewed by Pimpinan
            $pendingLeaderAnomalies = AnomalyFlag::where('needs_leader_action', true)
                ->whereNull('leader_reviewed_at')
                ->where('is_dismissed', false)
                ->orderByDesc('detected_at')
                ->limit(5)
                ->with(['transaction:id,description,amount,type'])
                ->get();

            foreach ($pendingLeaderAnomalies as $flag) {
                $desc = mb_substr($flag->transaction?->description ?? '', 0, 30);
                $amt = number_format($flag->transaction?->amount ?? 0, 0, ',', '.');
                $items[] = [
                    'id' => 'leader_anomaly_' . $flag->id,
                    'type' => 'leader_action',
                    'severity' => $flag->severity,
                    'title' => 'Otorisasi Anomali Diperlukan',
                    'message' => "Transaksi '{$desc}...' bernilai Rp {$amt} memerlukan keputusan otorisasi Anda.",
                    'url' => '/anomalies/check?flag_id=' . $flag->id,
                    'time' => $flag->detected_at?->toISOString(),
                    'read' => false,
                ];
            }

            // 2. Recent decisions by Pimpinan (last 7 days)
            $recentLeaderDecisions = AnomalyFlag::where('needs_leader_action', true)
                ->whereNotNull('leader_reviewed_at')
                ->where('is_dismissed', false)
                ->where('leader_reviewed_at', '>=', now()->subDays(7))
                ->orderByDesc('leader_reviewed_at')
                ->limit(3)
                ->with(['transaction:id,description,amount,type'])
                ->get();

            foreach ($recentLeaderDecisions as $flag) {
                $status = $flag->is_approved_by_leader ? 'Disetujui' : 'Ditolak';
                $desc = mb_substr($flag->transaction?->description ?? '', 0, 30);
                $amt = number_format($flag->transaction?->amount ?? 0, 0, ',', '.');
                $items[] = [
                    'id' => 'leader_decision_' . $flag->id,
                    'type' => 'leader_decision',
                    'severity' => $flag->is_approved_by_leader ? 'INFO' : 'WARNING',
                    'title' => "Keputusan Otorisasi: {$status}",
                    'message' => "Transaksi '{$desc}...' bernilai Rp {$amt} telah {$status} oleh Anda.",
                    'url' => '/anomalies/check?flag_id=' . $flag->id,
                    'time' => $flag->leader_reviewed_at?->toISOString(),
                    'read' => true,
                ];
            }
        }

        // Sort: Unread first, then newest first
        usort($items, function($a, $b) {
            if ($a['read'] !== $b['read']) {
                return $a['read'] ? 1 : -1;
            }
            return strcmp($b['time'] ?? '', $a['time'] ?? '');
        });

        $unreadCount = collect($items)->where('read', false)->count();

        return [
            'items' => array_slice($items, 0, 8),
            'unread_count' => $unreadCount,
        ];
    }
}
