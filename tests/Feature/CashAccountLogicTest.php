<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Transaction;
use App\Models\AnomalyFlag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashAccountLogicTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_account_anomaly_detection_works_seamlessly(): void
    {
        // 1. Create admin user
        $user = User::factory()->create(['role' => 'ADMIN_KEUANGAN']);

        // 2. Create manual cash transaction (bank_account_id is null) that is above Rp 10 Million
        // source must be CASH_MANUAL and type DEBIT to be picked up by Strategy 1 (instant)
        $tx = Transaction::create([
            'transaction_date' => now(),
            'description' => 'SETORAN TUNAI MANUAL KAS UTAMA',
            'amount' => 12000000.00,
            'type' => 'DEBIT',
            'source' => 'CASH_MANUAL',
            'bank_account_id' => null,
            'deduplication_hash' => 'TEST_CASH_123',
            'classification_method' => 'UNCLASSIFIED',
            'confidence_score' => 0,
        ]);

        // 3. Trigger anomaly detection for 'cash'
        $response = $this
            ->actingAs($user)
            ->post('/anomalies/detect', [
                'account_id' => 'cash',
            ]);

        // 4. Assert response redirects and session has no error
        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        // 5. Assert anomaly flag was created successfully
        $this->assertTrue(AnomalyFlag::where('transaction_id', $tx->id)->exists());
        $flag = AnomalyFlag::where('transaction_id', $tx->id)->first();
        $this->assertEquals('INCOME_INSTANT', $flag->detection_method);
        $this->assertEquals('MEDIUM', $flag->severity);
    }
}
