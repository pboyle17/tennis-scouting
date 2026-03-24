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
        Schema::table('leagues', function (Blueprint $table) {
            $table->timestamp('utr_last_updated_at')->nullable()->after('active');
            $table->timestamp('teams_last_synced_at')->nullable()->after('utr_last_updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            $table->dropColumn(['utr_last_updated_at', 'teams_last_synced_at']);
        });
    }
};
