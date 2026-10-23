<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * USTA member id (from USTA tournament registrations) and World Tennis Number (ITF) tennis id.
     */
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->string('usta_id')->nullable()->unique();
            $table->string('wtn_id')->nullable()->unique();
        });

        // Players whose WTN link was entered by hand: ...player-profile?tennis-id=DEM9468872
        foreach (DB::table('players')->whereNotNull('tennis_number_link')->get(['id', 'tennis_number_link']) as $row) {
            if (preg_match('/tennis-id=([A-Z0-9]+)/i', $row->tennis_number_link, $m)
                && !DB::table('players')->where('wtn_id', strtoupper($m[1]))->exists()) {
                DB::table('players')->where('id', $row->id)->update(['wtn_id' => strtoupper($m[1])]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn(['usta_id', 'wtn_id']);
        });
    }
};
