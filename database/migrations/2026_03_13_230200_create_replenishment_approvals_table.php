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
        Schema::create('replenishment_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('replenishment_id')->constrained('replenishments')->cascadeOnDelete();
            $table->unsignedInteger('step_order');
            $table->string('role_name');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->enum('status', ['pending', 'approved', 'declined'])->default('pending');
            $table->dateTime('acted_at')->nullable();
            $table->timestamps();

            $table->index(['replenishment_id', 'status', 'step_order'], 'rep_approvals_pending_idx');
            $table->index(['role_name', 'status'], 'rep_approvals_role_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('replenishment_approvals');
    }
};

