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
            $table->boolean('can_approve')
                ->default(true)
                ->after('role_name');
            $table->boolean('can_reject')
                ->default(true)
                ->after('can_approve');
            $table->boolean('use_item_selection')
                ->default(true)
                ->after('can_reject');
            $table->json('form_schema')
                ->nullable()
                ->after('use_item_selection');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('replenishment_approval_rule_steps', function (Blueprint $table) {
            $table->dropColumn([
                'can_approve',
                'can_reject',
                'use_item_selection',
                'form_schema',
            ]);
        });
    }
};

