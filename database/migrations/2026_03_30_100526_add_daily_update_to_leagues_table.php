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
            $table->boolean('daily_update')->default(false)->after('teams_last_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            $table->dropColumn('daily_update');
        });
    }
};
