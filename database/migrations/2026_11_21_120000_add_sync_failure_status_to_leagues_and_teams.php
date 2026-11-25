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
            $table->string('last_schedule_sync_status')->nullable();
            $table->text('last_schedule_sync_message')->nullable();
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->text('last_roster_sync_error')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            $table->dropColumn(['last_schedule_sync_status', 'last_schedule_sync_message']);
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('last_roster_sync_error');
        });
    }
};
