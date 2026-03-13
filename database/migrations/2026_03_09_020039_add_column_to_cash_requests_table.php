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
            $table->boolean('is_approved_the_authority_to_deduct')->default(false)->after('is_approved_by_treasury_manager');
            $table->string('dv_number')->nullable()->after('voucher_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_requests', function (Blueprint $table) {
            $table->dropColumn(['is_approved_the_authority_to_deduct', 'dv_number']);
        });
    }
};
