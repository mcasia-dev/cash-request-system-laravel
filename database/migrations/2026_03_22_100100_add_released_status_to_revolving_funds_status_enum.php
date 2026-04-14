<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE revolving_funds
            MODIFY status ENUM('pending', 'in progress', 'approved', 'rejected', 'replenished', 'released')
            NULL DEFAULT 'pending'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE revolving_funds
            MODIFY status ENUM('pending', 'in progress', 'approved', 'rejected', 'replenished')
            NULL DEFAULT 'pending'
        ");
    }
};
