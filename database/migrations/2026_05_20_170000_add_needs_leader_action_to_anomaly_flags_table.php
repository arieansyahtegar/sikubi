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
            $table->boolean('needs_leader_action')->default(false)->after('is_dismissed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('anomaly_flags', function (Blueprint $table) {
            $table->dropColumn('needs_leader_action');
        });
    }
};
