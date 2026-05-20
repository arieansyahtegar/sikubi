<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('anomaly_flags', function (Blueprint $table) {
            $table->boolean('is_approved_by_leader')->nullable()->default(null)->after('review_note');
            $table->text('leader_note')->nullable()->after('is_approved_by_leader');
            $table->timestamp('leader_reviewed_at')->nullable()->after('leader_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('anomaly_flags', function (Blueprint $table) {
            $table->dropColumn(['is_approved_by_leader', 'leader_note', 'leader_reviewed_at']);
        });
    }
};
