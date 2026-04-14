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
            $table->json('step_form_data')
                ->nullable()
                ->after('acted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('replenishment_approvals', function (Blueprint $table) {
            $table->dropColumn('step_form_data');
        });
    }
};

