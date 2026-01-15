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
        Schema::table('string_jobs', function (Blueprint $table) {
            $table->boolean('is_hybrid')->default(false)->after('racket_id');

            // Mains strings (for hybrid setups)
            $table->string('mains_brand')->nullable()->after('is_hybrid');
            $table->string('mains_model')->nullable()->after('mains_brand');
            $table->string('mains_gauge', 50)->nullable()->after('mains_model');
            $table->decimal('mains_tension', 5, 1)->nullable()->after('mains_gauge');

            // Crosses strings (for hybrid setups)
            $table->string('crosses_brand')->nullable()->after('mains_tension');
            $table->string('crosses_model')->nullable()->after('crosses_brand');
            $table->string('crosses_gauge', 50)->nullable()->after('crosses_model');
            $table->decimal('crosses_tension', 5, 1)->nullable()->after('crosses_gauge');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('string_jobs', function (Blueprint $table) {
            $table->dropColumn([
                'is_hybrid',
                'mains_brand',
                'mains_model',
                'mains_gauge',
                'mains_tension',
                'crosses_brand',
                'crosses_model',
                'crosses_gauge',
                'crosses_tension',
            ]);
        });
    }
};
