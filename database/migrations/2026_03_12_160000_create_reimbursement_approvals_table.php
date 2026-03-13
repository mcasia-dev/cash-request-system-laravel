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
        Schema::create('reimbursement_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reimbursement_id');
            $table->unsignedInteger('step_no');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('role_name');
            $table->boolean('required')->default(true);
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();

            $table->index(['reimbursement_id', 'status', 'step_no'], 'reim_approvals_lookup_idx');
            $table->unique(['reimbursement_id', 'step_no'], 'reim_approvals_unique_step_per_reim');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reimbursement_approvals');
    }
};

