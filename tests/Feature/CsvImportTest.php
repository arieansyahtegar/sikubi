<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\BankAccount;
use App\Models\ImportBatch;
use App\Models\Transaction;
use App\Models\AnomalyFlag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CsvImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_permanently_delete_all_import_history_in_one_click(): void
    {
        // 1. Create admin user
        $user = User::factory()->create(['role' => 'ADMIN_KEUANGAN']);

        // 2. Create bank account
        $account = BankAccount::create([
            'bank_name' => 'Bank Mandiri',
            'account_number' => '1430028073946',
            'account_alias' => 'MANDIRI_TEST',
        ]);

        // 3. Create dummy import batches (one active, one soft-deleted)
        $batch1 = ImportBatch::create([
            'bank_account_id' => $account->id,
            'uploaded_by' => $user->id,
            'file_name' => 'statement_active.csv',
            'bank_format' => 'BCA',
            'total_rows' => 2,
            'success_rows' => 2,
            'status' => 'COMPLETED',
            'imported_at' => now(),
        ]);

        $batch2 = ImportBatch::create([
            'bank_account_id' => $account->id,
            'uploaded_by' => $user->id,
            'file_name' => 'statement_trashed.csv',
            'bank_format' => 'BCA',
            'total_rows' => 1,
            'success_rows' => 1,
            'status' => 'COMPLETED',
            'imported_at' => now()->subDay(),
        ]);
        $batch2->delete(); // Soft delete it

        // 4. Create dummy transactions
        $tx1 = Transaction::create([
            'import_batch_id' => $batch1->id,
            'bank_account_id' => $account->id,
            'transaction_date' => now(),
            'description' => 'TEST TX 1',
            'amount' => 50000.00,
            'type' => 'DEBIT',
            'deduplication_hash' => 'HASH_1',
            'source' => 'IMPORT',
        ]);

        $tx2 = Transaction::create([
            'import_batch_id' => $batch1->id,
            'bank_account_id' => $account->id,
            'transaction_date' => now(),
            'description' => 'TEST TX 2',
            'amount' => 25000.00,
            'type' => 'CREDIT',
            'deduplication_hash' => 'HASH_2',
            'source' => 'IMPORT',
        ]);

        $tx3 = Transaction::create([
            'import_batch_id' => $batch2->id,
            'bank_account_id' => $account->id,
            'transaction_date' => now()->subDay(),
            'description' => 'TEST TX 3',
            'amount' => 100000.00,
            'type' => 'DEBIT',
            'deduplication_hash' => 'HASH_3',
            'source' => 'IMPORT',
        ]);
        $tx3->delete(); // Soft delete it

        // Create anomaly flag
        AnomalyFlag::create([
            'transaction_id' => $tx1->id,
            'detection_method' => 'INCOME_INSTANT',
            'score' => 1.0,
            'severity' => 'HIGH',
            'reason' => 'Test reason',
        ]);

        // Assert initial counts
        $this->assertEquals(1, ImportBatch::count());
        $this->assertEquals(2, ImportBatch::withTrashed()->count());
        $this->assertEquals(2, Transaction::count());
        $this->assertEquals(3, Transaction::withTrashed()->count());
        $this->assertEquals(1, AnomalyFlag::count());

        // 5. Trigger destroyAll (DELETE /import/all)
        $response = $this
            ->actingAs($user)
            ->delete('/import/all');

        // 6. Assert redirect and no errors
        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('importResult');

        // 7. Verify all history is permanently deleted from DB
        $this->assertEquals(0, ImportBatch::withTrashed()->count());
        $this->assertEquals(0, Transaction::withTrashed()->count());
        $this->assertEquals(0, AnomalyFlag::count());
    }
}
