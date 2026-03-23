<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("SELECT setval('rackets_id_seq', (SELECT MAX(id) FROM rackets))");
    }

    public function down(): void
    {
        // Not reversible
    }
};
