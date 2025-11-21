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
            $table->boolean('utr_singles_reliable')->default(false)->after('utr_singles_rating');
            $table->boolean('utr_doubles_reliable')->default(false)->after('utr_doubles_rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn(['utr_singles_reliable', 'utr_doubles_reliable']);
        });
    }
};
