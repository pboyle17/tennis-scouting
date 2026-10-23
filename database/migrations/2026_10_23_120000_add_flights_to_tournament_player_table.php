<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The flights (draws / events) each player entered in a tournament, e.g. ["NTRP 4.0 Men's 18 & over singles"].
     */
    public function up(): void
    {
        Schema::table('tournament_player', function (Blueprint $table) {
            $table->json('flights')->nullable();
        });

        // Backfill tournaments already synced from UTR: a player's flights are the draws they played in
        $rows = DB::table('tournament_match_players as tmp')
            ->join('tournament_matches as tm', 'tm.id', '=', 'tmp.tournament_match_id')
            ->join('tournament_draws as td', 'td.id', '=', 'tm.tournament_draw_id')
            ->select('td.tournament_id', 'tmp.player_id', 'td.name')
            ->distinct()
            ->orderBy('td.name')
            ->get()
            ->groupBy(fn($row) => $row->tournament_id . ':' . $row->player_id);

        foreach ($rows as $group) {
            $first = $group->first();
            DB::table('tournament_player')
                ->where('tournament_id', $first->tournament_id)
                ->where('player_id', $first->player_id)
                ->update(['flights' => json_encode($group->pluck('name')->values()->all())]);
        }
    }

    public function down(): void
    {
        Schema::table('tournament_player', function (Blueprint $table) {
            $table->dropColumn('flights');
        });
    }
};
