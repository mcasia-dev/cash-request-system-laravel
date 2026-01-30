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
            $table->string('request_no')->after('user_id')->unique();
            $table->string('nature_of_payment')->after('requesting_amount')->nullable();
            $table->string('payee')->after('nature_of_payment')->nullable();
            $table->string('payment_to')->after('payee')->nullable();
            $table->string('bank_account_no')->after('payment_to')->nullable();
            $table->string('bank_name')->after('bank_account_no')->nullable();
            $table->string('account_type')->after('bank_name')->nullable();
            $table->string('cc_holder_name')->after('account_type')->nullable();
            $table->string('cc_number')->after('cc_holder_name')->nullable();
            $table->string('cc_type')->after('cc_number')->nullable();
            $table->string('cc_expiration')->after('cc_type')->nullable();
            $table->date('date_liquidated')->after('due_date')->nullable();
            $table->date('date_released')->after('date_liquidated')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_requests', function (Blueprint $table) {
            $table->dropUnique(['request_no']);
            $table->dropColumn([
                'nature_of_payment',
                'payee',
                'payment_to',
                'bank_account_no',
                'bank_name',
                'account_type',
                'cc_holder_name',
                'cc_number',
                'cc_type',
                'cc_expiration',
                'date_liquidated',
                'date_released',
            ]);
        });
    }
};
