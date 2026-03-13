<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cash_requests', function (Blueprint $table) {
            $table->enum('mode_of_transfer', ['for deposit', 'for pickup'])->default('for pickup')->after('nature_of_request');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_requests', function (Blueprint $table) {
            $table->dropColumn('mode_of_transfer');
        });
    }
};
