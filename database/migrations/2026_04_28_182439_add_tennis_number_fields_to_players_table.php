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
        Schema::table('players', function (Blueprint $table) {
            $table->decimal('tennis_number_singles_rating', 8, 2)->nullable()->after('USTA_dynamic_rating');
            $table->decimal('tennis_number_doubles_rating', 8, 2)->nullable()->after('tennis_number_singles_rating');
            $table->string('tennis_number_link')->nullable()->after('tennis_number_doubles_rating');
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn(['tennis_number_singles_rating', 'tennis_number_doubles_rating', 'tennis_number_link']);
        });
    }
};
