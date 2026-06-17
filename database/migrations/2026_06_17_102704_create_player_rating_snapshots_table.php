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
        Schema::create('player_rating_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->date('snapshot_date');
            $table->decimal('utr_singles_rating', 5, 2)->nullable();
            $table->decimal('utr_doubles_rating', 5, 2)->nullable();
            $table->decimal('usta_dynamic_rating', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['player_id', 'snapshot_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_rating_snapshots');
    }
};
