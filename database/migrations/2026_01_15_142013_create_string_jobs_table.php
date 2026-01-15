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
        Schema::create('string_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('racket_id')->constrained()->cascadeOnDelete();
            $table->string('string_brand');
            $table->string('string_model')->nullable();
            $table->string('string_gauge', 50)->nullable();
            $table->decimal('tension', 5, 1);
            $table->date('stringing_date');
            $table->decimal('time_played', 6, 1)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_current')->default(true);
            $table->timestamps();

            $table->index(['racket_id', 'is_current']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('string_jobs');
    }
};
