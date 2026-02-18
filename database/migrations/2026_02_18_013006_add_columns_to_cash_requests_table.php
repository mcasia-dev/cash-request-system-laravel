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
            $table->enum('disbursement_type', ['check', 'payroll'])->nullable()->after('payment_to');
            $table->string('check_branch_name')->nullable()->after('disbursement_type');
            $table->string('check_no')->nullable()->after('check_branch_name');
            $table->date('cut_off_date')->nullable()->after('check_no');
            $table->integer('payroll_credit')->nullable()->after('cut_off_date');
            $table->date('payroll_date')->nullable()->after('payroll_credit');
            $table->unsignedBigInteger('disbursement_added_by')->nullable()->after('payroll_credit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_requests', function (Blueprint $table) {
            $table->dropColumn([
                'disbursement_type',
                'check_branch_name',
                'check_no',
                'cut_off_date',
                'payroll_credit',
                'disbursement_added_by',
            ]);
        });
    }
};
