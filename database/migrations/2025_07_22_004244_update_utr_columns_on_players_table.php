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
        $table->renameColumn('utr_rating', 'utr_singles_rating');
        $table->float('utr_doubles_rating')->nullable();
      });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
      Schema::table('players', function (Blueprint $table) {
        $table->renameColumn('utr_singles_rating', 'utr_rating');
        $table->dropColumn('utr_doubles_rating');
      });
    }
};
