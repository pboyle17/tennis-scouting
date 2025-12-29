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
        Schema::create('court_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('court_id')->constrained('courts')->onDelete('cascade');
            $table->foreignId('player_id')->constrained('players')->onDelete('cascade');
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->boolean('won')->default(false);
            $table->decimal('utr_singles_rating', 5, 2)->nullable();
            $table->decimal('utr_doubles_rating', 5, 2)->nullable();
            $table->decimal('usta_dynamic_rating', 4, 2)->nullable();
            $table->timestamps();

            $table->index(['court_id', 'player_id']);
            $table->index(['player_id', 'team_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('court_players');
    }
};
