<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BankAccountController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');

        // If date filters are present, count only transactions within that range
        if ($dateFrom || $dateTo) {
            $accounts = BankAccount::withCount(['transactions' => function ($q) use ($dateFrom, $dateTo) {
                if ($dateFrom) {
                    $q->where('transaction_date', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $q->where('transaction_date', '<=', $dateTo);
                }
            }])->get();
        } else {
            $accounts = BankAccount::withCount('transactions')->get();
        }

        return Inertia::render('Accounts', [
            'accounts'  => $accounts,
            'filters'   => [
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'bank_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'account_alias' => 'nullable|string|max:100',
            'currency' => 'nullable|string|max:10',
        ]);

        BankAccount::create($request->only(['bank_name', 'account_number', 'account_alias', 'currency']));
        return back();
    }

    public function destroy(BankAccount $account)
    {
        if ($account->transactions()->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'bank_account' => 'Tidak dapat menghapus rekening bank yang memiliki transaksi aktif. Selesaikan atau hapus transaksi terlebih dahulu.'
            ]);
        }

        $account->delete();
        return back();
    }

    public function update(Request $request, BankAccount $account)
    {
        $request->validate([
            'bank_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'account_alias' => 'nullable|string|max:100',
            'currency' => 'nullable|string|max:10',
        ]);

        $account->update($request->only(['bank_name', 'account_number', 'account_alias', 'currency']));
        return back();
    }
}
