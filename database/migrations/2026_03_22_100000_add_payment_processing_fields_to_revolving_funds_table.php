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
        Schema::table('revolving_funds', function (Blueprint $table) {
            $table->enum('disbursement_type', ['check', 'payroll'])->nullable()->after('revolving_fund_mode_of_transfer_id');
            $table->unsignedBigInteger('disbursement_added_by')->nullable()->after('disbursement_type');
            $table->string('check_branch_name')->nullable()->after('disbursement_added_by');
            $table->string('check_no')->nullable()->after('check_branch_name');
            $table->string('dv_number')->nullable()->after('check_no');
            $table->string('voucher_no')->nullable()->after('dv_number');
            $table->date('cut_off_date')->nullable()->after('voucher_no');
            $table->boolean('is_override')->default(false)->after('cut_off_date');
            $table->boolean('is_approved_by_treasury_manager')->default(false)->after('is_override');
            $table->text('remarks')->nullable()->after('is_approved_by_treasury_manager');
            $table->date('releasing_date')->nullable()->after('remarks');
            $table->time('releasing_time_from')->nullable()->after('releasing_date');
            $table->time('releasing_time_to')->nullable()->after('releasing_time_from');
            $table->unsignedBigInteger('released_by')->nullable()->after('releasing_time_to');
            $table->timestamp('released_at')->nullable()->after('released_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('revolving_funds', function (Blueprint $table) {
            $table->dropColumn([
                'disbursement_type',
                'disbursement_added_by',
                'check_branch_name',
                'check_no',
                'dv_number',
                'voucher_no',
                'cut_off_date',
                'is_override',
                'is_approved_by_treasury_manager',
                'remarks',
                'releasing_date',
                'releasing_time_from',
                'releasing_time_to',
                'released_by',
                'released_at',
            ]);
        });
    }
};
