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
            // Make regular string fields nullable to support hybrid strings
            $table->string('string_brand')->nullable()->change();
            $table->decimal('tension', 5, 1)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('string_jobs', function (Blueprint $table) {
            // Revert to NOT NULL (but this might fail if there are null values)
            $table->string('string_brand')->nullable(false)->change();
            $table->decimal('tension', 5, 1)->nullable(false)->change();
        });
    }
};
