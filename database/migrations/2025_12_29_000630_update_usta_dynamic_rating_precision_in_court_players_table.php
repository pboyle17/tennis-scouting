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
        Schema::table('court_players', function (Blueprint $table) {
            // Change from decimal(3,1) to decimal(4,2) for hundredths precision
            $table->decimal('usta_dynamic_rating', 4, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('court_players', function (Blueprint $table) {
            // Revert back to decimal(3,1)
            $table->decimal('usta_dynamic_rating', 3, 1)->nullable()->change();
        });
    }
};
