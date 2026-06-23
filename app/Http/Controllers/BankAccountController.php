<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BankAccountController extends Controller
{
    public function index(Request $request)
    {
        $accounts = BankAccount::withCount('transactions')->get();
        
        $selectedAccountId = $request->input('account_id');
        if (!$selectedAccountId && $accounts->isNotEmpty()) {
            $selectedAccountId = (string) $accounts->first()->id;
        }
        
        $transactions = null;
        
        if ($selectedAccountId) {
            $query = Transaction::where('bank_account_id', $selectedAccountId)
                ->with('category:id,name,color')
                ->orderByDesc('transaction_date');
                
            if ($dateFrom = $request->input('date_from')) {
                $query->where('transaction_date', '>=', $dateFrom);
            }
            if ($dateTo = $request->input('date_to')) {
                $query->where('transaction_date', '<=', $dateTo);
            }
            
            $transactions = $query->paginate(20)->withQueryString();
        }

        return Inertia::render('Accounts', [
            'accounts' => $accounts,
            'transactions' => $transactions,
            'filters' => [
                'account_id' => $selectedAccountId,
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
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
