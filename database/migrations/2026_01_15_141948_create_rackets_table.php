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
        Schema::create('rackets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('brand');
            $table->string('model');
            $table->decimal('weight', 5, 1)->nullable();
            $table->integer('swing_weight')->nullable();
            $table->decimal('balance_point', 5, 1)->nullable();
            $table->string('string_pattern', 50)->nullable();
            $table->string('grip_size', 50)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rackets');
    }
};
