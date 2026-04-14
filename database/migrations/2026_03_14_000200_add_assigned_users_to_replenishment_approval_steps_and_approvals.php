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
        Schema::table('replenishment_approval_rule_steps', function (Blueprint $table) {
            $table->json('assigned_user_ids')
                ->nullable()
                ->after('form_schema');
        });

        Schema::table('replenishment_approvals', function (Blueprint $table) {
            $table->json('assigned_user_ids')
                ->nullable()
                ->after('step_form_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('replenishment_approvals', function (Blueprint $table) {
            $table->dropColumn('assigned_user_ids');
        });

        Schema::table('replenishment_approval_rule_steps', function (Blueprint $table) {
            $table->dropColumn('assigned_user_ids');
        });
    }
};

