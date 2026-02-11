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
            $table->string('activity_name')->nullable()->change();
            $table->date('activity_date')->nullable()->change();
            $table->string('voucher_no')->nullable()->after('payment_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_requests', function (Blueprint $table) {
            $table->string('activity_name')->change();
            $table->date('activity_date')->change();
            $table->dropColumn('voucher_no');
        });
    }
};
