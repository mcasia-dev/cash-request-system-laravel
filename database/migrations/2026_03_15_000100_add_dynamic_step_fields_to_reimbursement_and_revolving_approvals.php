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
            $table->boolean('can_approve')
                ->default(true)
                ->after('assigned_user_ids');
            $table->boolean('can_reject')
                ->default(true)
                ->after('can_approve');
            $table->json('form_schema')
                ->nullable()
                ->after('can_reject');
        });

        Schema::table('reimbursement_approvals', function (Blueprint $table) {
            $table->boolean('can_approve')
                ->default(true)
                ->after('assigned_user_ids');
            $table->boolean('can_reject')
                ->default(true)
                ->after('can_approve');
            $table->json('step_form_data')
                ->nullable()
                ->after('can_reject');
        });

        Schema::table('revolving_fund_approval_rule_steps', function (Blueprint $table) {
            $table->boolean('can_approve')
                ->default(true)
                ->after('assigned_user_ids');
            $table->boolean('can_reject')
                ->default(true)
                ->after('can_approve');
            $table->json('form_schema')
                ->nullable()
                ->after('can_reject');
        });

        Schema::table('revolving_fund_approvals', function (Blueprint $table) {
            $table->boolean('can_approve')
                ->default(true)
                ->after('assigned_user_ids');
            $table->boolean('can_reject')
                ->default(true)
                ->after('can_approve');
            $table->json('step_form_data')
                ->nullable()
                ->after('can_reject');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('revolving_fund_approvals', function (Blueprint $table) {
            $table->dropColumn([
                'can_approve',
                'can_reject',
                'step_form_data',
            ]);
        });

        Schema::table('revolving_fund_approval_rule_steps', function (Blueprint $table) {
            $table->dropColumn([
                'can_approve',
                'can_reject',
                'form_schema',
            ]);
        });

        Schema::table('reimbursement_approvals', function (Blueprint $table) {
            $table->dropColumn([
                'can_approve',
                'can_reject',
                'step_form_data',
            ]);
        });

        Schema::table('reimbursement_mode_approvals', function (Blueprint $table) {
            $table->dropColumn([
                'can_approve',
                'can_reject',
                'form_schema',
            ]);
        });
    }
};

