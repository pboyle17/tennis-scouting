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
        Schema::create('courts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tennis_match_id')->constrained('tennis_matches')->onDelete('cascade');
            $table->string('court_type'); // 'singles' or 'doubles'
            $table->integer('court_number');
            $table->integer('home_score')->nullable();
            $table->integer('away_score')->nullable();
            $table->timestamps();

            $table->index(['tennis_match_id', 'court_type', 'court_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courts');
    }
};
