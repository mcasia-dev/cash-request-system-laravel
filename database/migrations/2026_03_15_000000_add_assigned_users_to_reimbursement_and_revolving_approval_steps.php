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
        Schema::table('reimbursement_mode_approvals', function (Blueprint $table) {
            $table->json('assigned_user_ids')
                ->nullable()
                ->after('required');
        });

        Schema::table('reimbursement_approvals', function (Blueprint $table) {
            $table->json('assigned_user_ids')
                ->nullable()
                ->after('acted_at');
        });

        Schema::table('revolving_fund_approval_rule_steps', function (Blueprint $table) {
            $table->json('assigned_user_ids')
                ->nullable()
                ->after('step_order');
        });

        Schema::table('revolving_fund_approvals', function (Blueprint $table) {
            $table->json('assigned_user_ids')
                ->nullable()
                ->after('acted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('revolving_fund_approvals', function (Blueprint $table) {
            $table->dropColumn('assigned_user_ids');
        });

        Schema::table('revolving_fund_approval_rule_steps', function (Blueprint $table) {
            $table->dropColumn('assigned_user_ids');
        });

        Schema::table('reimbursement_approvals', function (Blueprint $table) {
            $table->dropColumn('assigned_user_ids');
        });

        Schema::table('reimbursement_mode_approvals', function (Blueprint $table) {
            $table->dropColumn('assigned_user_ids');
        });
    }
};

