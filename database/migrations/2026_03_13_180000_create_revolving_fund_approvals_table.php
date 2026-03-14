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
        Schema::create('revolving_fund_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('revolving_fund_id')->constrained('revolving_funds')->cascadeOnDelete();
            $table->unsignedInteger('step_order');
            $table->string('role_name');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->enum('status', ['pending', 'approved', 'declined'])->default('pending');
            $table->dateTime('acted_at')->nullable();
            $table->timestamps();

            $table->index(['revolving_fund_id', 'status', 'step_order'], 'rf_approvals_pending_idx');
            $table->index(['role_name', 'status'], 'rf_approvals_role_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revolving_fund_approvals');
    }
};
