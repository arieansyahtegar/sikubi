<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add source column if it doesn't exist yet
        if (!Schema::hasColumn('transactions', 'source')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->string('source', 20)->default('IMPORT')->after('deduplication_hash');
            });
        }

        // Make bank_account_id nullable for cash transactions (SQLite rebuild)
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=off');

            // Drop old indexes to avoid naming conflicts on SQLite
            DB::statement('DROP INDEX IF EXISTS transactions_bank_account_id_transaction_date_index');
            DB::statement('DROP INDEX IF EXISTS transactions_type_category_id_index');
            DB::statement('DROP INDEX IF EXISTS transactions_transaction_date_index');
            DB::statement('DROP INDEX IF EXISTS transactions_source_index');
            DB::statement('DROP INDEX IF EXISTS transactions_deduplication_hash_unique');

            // Create temporary table with bank_account_id nullable
            Schema::create('transactions_temp', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('import_batch_id')->nullable();
                $table->unsignedBigInteger('bank_account_id')->nullable();
                $table->dateTime('transaction_date');
                $table->text('description');
                $table->decimal('amount', 15, 2);
                $table->string('type', 10);
                $table->unsignedBigInteger('category_id')->nullable();
                $table->string('classification_method', 30)->default('UNCLASSIFIED');
                $table->decimal('confidence_score', 3, 2)->default(0);
                $table->json('raw_data')->nullable();
                $table->string('deduplication_hash', 64);
                $table->string('source', 20)->default('IMPORT');
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('import_batch_id')->references('id')->on('import_batches')->onDelete('set null');
                $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('set null');
                $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');

                $table->unique('deduplication_hash', 'transactions_deduplication_hash_unique');
                $table->index(['bank_account_id', 'transaction_date'], 'transactions_bank_account_id_transaction_date_index');
                $table->index(['type', 'category_id'], 'transactions_type_category_id_index');
                $table->index('transaction_date', 'transactions_transaction_date_index');
                $table->index('source', 'transactions_source_index');
            });

            // Copy all existing data from original transactions table
            DB::statement('INSERT INTO transactions_temp (id, import_batch_id, bank_account_id, transaction_date, description, amount, type, category_id, classification_method, confidence_score, raw_data, deduplication_hash, source, created_at, updated_at, deleted_at) SELECT id, import_batch_id, bank_account_id, transaction_date, description, amount, type, category_id, classification_method, confidence_score, raw_data, deduplication_hash, source, created_at, updated_at, deleted_at FROM transactions');

            // Drop original table (foreign key references on other tables like anomaly_flags still point to 'transactions')
            DB::statement('DROP TABLE transactions');

            // Rename transactions_temp to transactions (restoring table name and repairing foreign keys)
            DB::statement('ALTER TABLE transactions_temp RENAME TO transactions');

            DB::statement('PRAGMA foreign_keys=on');
        } else {
            Schema::table('transactions', function (Blueprint $table) {
                $table->unsignedBigInteger('bank_account_id')->nullable()->change();
                $table->dropForeign(['bank_account_id']);
                $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('set null');
            });
        }

        // Add source index if missing
        try {
            Schema::table('transactions', function (Blueprint $table) {
                $table->index('source');
            });
        } catch (\Exception $e) {
            // Index already exists, ignore
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('transactions', 'source')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn('source');
            });
        }
    }
};
