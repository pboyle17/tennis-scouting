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
            // Remove the hybrid distinction column
            $table->dropColumn('is_hybrid');

            // Remove regular string columns (no longer needed)
            $table->dropColumn(['string_brand', 'string_model', 'string_gauge', 'tension']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('string_jobs', function (Blueprint $table) {
            // Restore hybrid distinction column
            $table->boolean('is_hybrid')->default(false)->after('racket_id');

            // Restore regular string columns
            $table->string('string_brand')->nullable()->after('is_hybrid');
            $table->string('string_model')->nullable()->after('string_brand');
            $table->string('string_gauge', 50)->nullable()->after('string_model');
            $table->decimal('tension', 5, 1)->nullable()->after('string_gauge');
        });
    }
};
