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
        Schema::table('replenishment_approvals', function (Blueprint $table) {
            $table->boolean('can_approve')->default(true)->after('assigned_user_ids');
            $table->boolean('can_reject')->default(true)->after('can_approve');
            $table->boolean('can_verify')->default(false)->after('can_reject');
            $table->boolean('can_replenish')->default(false)->after('can_verify');
            $table->boolean('use_item_selection')->default(true)->after('can_replenish');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('replenishment_approvals', function (Blueprint $table) {
            $table->dropColumn([
                'can_approve',
                'can_reject',
                'can_verify',
                'can_replenish',
                'use_item_selection',
            ]);
        });
    }
};
