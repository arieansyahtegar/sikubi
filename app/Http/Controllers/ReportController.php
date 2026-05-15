<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Category;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Show the printable monthly recap report page.
     */
    public function printRecap(Request $request)
    {
        $month = $request->input('month');
        $year  = $request->input('year');
        $accountId = $request->input('account_id');

        $accounts = BankAccount::all(['id', 'bank_name', 'account_alias']);

        // If no month/year selected, show selector only
        if (!$month || !$year) {
            return \Inertia\Inertia::render('Reports/PrintRecap', [
                'accounts'     => $accounts,
                'transactions' => null,
                'summary'      => null,
                'filters'      => [
                    'month'      => $month,
                    'year'       => $year,
                    'account_id' => $accountId,
                ],
            ]);
        }

        // Build date range for the selected month/year
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate   = $startDate->copy()->endOfMonth();

        // Query transactions
        $query = Transaction::with('category:id,name,color,type', 'bankAccount:id,bank_name,account_alias')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date')
            ->orderBy('id');

        if ($accountId) {
            $query->where('bank_account_id', $accountId);
        }

        $transactions = $query->get()->map(function ($tx, $index) {
            return [
                'no'          => $index + 1,
                'date'        => $tx->transaction_date->format('d/m/Y'),
                'description' => $tx->description,
                'type'        => $tx->type === 'DEBIT' ? 'Pendapatan' : 'Pengeluaran',
                'type_raw'    => $tx->type,
                'amount'      => $tx->amount,
                'category'    => $tx->category->name ?? '-',
                'account'     => $tx->bankAccount->account_alias ?? $tx->bankAccount->bank_name ?? '-',
            ];
        });

        // Calculate summary
        $totalDebit  = $transactions->where('type_raw', 'DEBIT')->sum('amount');
        $totalCredit = $transactions->where('type_raw', 'CREDIT')->sum('amount');

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return \Inertia\Inertia::render('Reports/PrintRecap', [
            'accounts'     => $accounts,
            'transactions' => $transactions->values(),
            'summary'      => [
                'month_label'   => $monthNames[(int)$month] . ' ' . $year,
                'total_debit'   => round($totalDebit),
                'total_credit'  => round($totalCredit),
                'balance'       => round($totalDebit - $totalCredit),
            ],
            'filters' => [
                'month'      => $month,
                'year'       => $year,
                'account_id' => $accountId,
            ],
        ]);
    }

    public function recapCsv(Request $request)
    {
        $accountId = $request->input('account_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if ($dateFrom === 'null' || $dateFrom === '' || !$dateFrom) $dateFrom = null;
        if ($dateTo === 'null' || $dateTo === '' || !$dateTo) $dateTo = null;

        $fileName = 'rekap_mutasi_' . now()->format('Ymd_His') . '.csv';
        
        // Use system temp directory for maximum compatibility
        $tempFile = tempnam(sys_get_temp_dir(), 'sikubi_');
        $out = fopen($tempFile, 'w');
        
        // BOM for Excel UTF-8
        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, ['LAPORAN REKAP MUTASI KEUANGAN SIKUBI']);
        fputcsv($out, ['Dicetak Pada:', now()->format('Y-m-d H:i:s')]);
        if ($dateFrom || $dateTo) {
            fputcsv($out, ['Periode:', ($dateFrom ?? 'Awal') . ' s/d ' . ($dateTo ?? 'Sekarang')]);
        }
        fputcsv($out, []);

        // Summary Accounts
        fputcsv($out, ['RINGKASAN PER REKENING']);
        fputcsv($out, ['Nama Bank', 'Alias', 'Total Masuk', 'Total Keluar', 'Saldo']);
        
        $accountSummaryQuery = Transaction::query()
            ->select('bank_account_id', 'type', \Illuminate\Support\Facades\DB::raw('SUM(amount) as total'))
            ->groupBy('bank_account_id', 'type');

        if ($accountId) $accountSummaryQuery->where('bank_account_id', $accountId);
        if ($dateFrom) $accountSummaryQuery->where('transaction_date', '>=', $dateFrom);
        if ($dateTo) $accountSummaryQuery->where('transaction_date', '<=', $dateTo);

        $accountTotals = $accountSummaryQuery->get()->groupBy('bank_account_id');
        
        $accounts = BankAccount::query();
        if ($accountId) $accounts->where('id', $accountId);
        
        foreach ($accounts->get() as $acc) {
            $accTotals = $accountTotals->get($acc->id, collect());
            $debit = $accTotals->where('type', 'DEBIT')->first()->total ?? 0;
            $credit = $accTotals->where('type', 'CREDIT')->first()->total ?? 0;
            
            fputcsv($out, [$acc->bank_name, $acc->account_alias ?? '-', $debit, $credit, $debit - $credit]);
        }
        fputcsv($out, []);

        // Summary Categories
        fputcsv($out, ['RINGKASAN PER KATEGORI']);
        fputcsv($out, ['Kategori', 'Tipe', 'Jml Transaksi', 'Total Nominal']);

        $categorySummaryQuery = Transaction::query()
            ->select('category_id', 'type', \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'), \Illuminate\Support\Facades\DB::raw('SUM(amount) as total'))
            ->groupBy('category_id', 'type');

        if ($accountId) $categorySummaryQuery->where('bank_account_id', $accountId);
        if ($dateFrom) $categorySummaryQuery->where('transaction_date', '>=', $dateFrom);
        if ($dateTo) $categorySummaryQuery->where('transaction_date', '<=', $dateTo);

        $categoryTotals = $categorySummaryQuery->get()->groupBy('category_id');

        $categories = Category::all();
        foreach ($categories as $cat) {
            $catTotals = $categoryTotals->get($cat->id, collect());
            foreach ($catTotals as $totalData) {
                if ($totalData->count > 0) {
                    fputcsv($out, [$cat->name, $totalData->type === 'DEBIT' ? 'Masuk' : 'Keluar', $totalData->count, $totalData->total]);
                }
            }
        }
        
        // Also include unclassified transactions
        $unclassifiedTotals = $categoryTotals->get(null, collect());
        foreach ($unclassifiedTotals as $totalData) {
            if ($totalData->count > 0) {
                fputcsv($out, ['Belum Terkategori', $totalData->type === 'DEBIT' ? 'Masuk' : 'Keluar', $totalData->count, $totalData->total]);
            }
        }
        
        fputcsv($out, []);

        // Details
        fputcsv($out, ['DETAIL TRANSAKSI']);
        fputcsv($out, ['Tanggal', 'Deskripsi', 'Kategori', 'Tipe', 'Rekening', 'Jumlah']);

        $txQuery = Transaction::with(['category', 'bankAccount'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id');
        
        if ($accountId) $txQuery->where('bank_account_id', $accountId);
        if ($dateFrom) $txQuery->where('transaction_date', '>=', $dateFrom);
        if ($dateTo) $txQuery->where('transaction_date', '<=', $dateTo);

        $txQuery->chunk(500, function ($txs) use ($out) {
            foreach ($txs as $tx) {
                fputcsv($out, [
                    $tx->transaction_date->format('Y-m-d'),
                    $tx->description,
                    $tx->category->name ?? 'Unclassified',
                    $tx->type,
                    $tx->bankAccount->account_alias ?? $tx->bankAccount->bank_name ?? '-',
                    $tx->amount
                ]);
            }
        });

        fclose($out);

        return response()->download($tempFile, $fileName, [
            'Content-Type' => 'text/csv',
        ])->deleteFileAfterSend(true);
    }
}
