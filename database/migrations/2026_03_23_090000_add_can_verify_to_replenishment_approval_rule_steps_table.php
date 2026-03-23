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
            $table->boolean('can_verify')->default(false)->after('can_reject');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('replenishment_approval_rule_steps', function (Blueprint $table) {
            $table->dropColumn('can_verify');
        });
    }
};
