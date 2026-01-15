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
        Schema::table('rackets', function (Blueprint $table) {
            $table->dropColumn('balance_point');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rackets', function (Blueprint $table) {
            $table->decimal('balance_point', 5, 1)->nullable()->after('swing_weight');
        });
    }
};
