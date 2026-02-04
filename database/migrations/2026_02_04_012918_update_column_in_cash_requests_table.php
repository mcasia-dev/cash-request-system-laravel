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
        Schema::table('cash_requests', function (Blueprint $table) {
            $table->datetime('date_released')->after('date_liquidated')->nullable()->change();
            $table->datetime('date_liquidated')->after('due_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_requests', function (Blueprint $table) {
            $table->date('date_released')->after('date_liquidated')->nullable()->change();
            $table->date('date_liquidated')->after('due_date')->nullable()->change();
        });
    }
};
