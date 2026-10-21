<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Draws and match results for tournaments, synced from UTR imported events.
     * Replaces the free-text tournament "flight" field.
     */
    public function up(): void
    {
        Schema::create('tournament_draws', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('utr_draw_id')->nullable();
            $table->string('name');
            $table->string('team_type')->nullable(); // singles | doubles
            $table->string('gender')->nullable();
            $table->unsignedInteger('player_count')->nullable();
            $table->timestamps();

            $table->unique(['tournament_id', 'utr_draw_id']);
        });

        Schema::create('tournament_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_draw_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('utr_result_id')->nullable()->unique();
            $table->date('played_on')->nullable();
            $table->string('round_code')->nullable(); // e.g. QF, SF, F, C-F
            $table->string('round_name')->nullable();
            $table->string('outcome')->nullable(); // completed | walkover | ...
            $table->string('completion_type')->nullable(); // winnercompleted | winnerdidnotstart | ...
            $table->timestamps();
        });

        Schema::create('tournament_match_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_match_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->boolean('won');
            $table->unsignedTinyInteger('position')->default(1); // 1 or 2 (doubles partner)
            // Ratings as reported by UTR when first synced
            $table->decimal('utr_singles_rating', 5, 2)->nullable();
            $table->decimal('utr_doubles_rating', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['tournament_match_id', 'player_id']);
        });

        Schema::create('tournament_match_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_match_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('set_number');
            $table->unsignedTinyInteger('winner_score');
            $table->unsignedTinyInteger('loser_score');
            $table->unsignedTinyInteger('winner_tiebreak')->nullable();
            $table->unsignedTinyInteger('loser_tiebreak')->nullable();
            $table->timestamps();

            $table->unique(['tournament_match_id', 'set_number']);
        });

        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn('flight');
            $table->timestamp('utr_last_synced_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->string('flight')->nullable()->after('name');
            $table->dropColumn('utr_last_synced_at');
        });

        Schema::dropIfExists('tournament_match_sets');
        Schema::dropIfExists('tournament_match_players');
        Schema::dropIfExists('tournament_matches');
        Schema::dropIfExists('tournament_draws');
    }
};
