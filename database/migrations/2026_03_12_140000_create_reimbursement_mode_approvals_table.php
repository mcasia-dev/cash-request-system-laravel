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
        Schema::create('reimbursement_mode_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reimbursement_mode_id');
            $table->unsignedInteger('step_no');
            $table->unsignedBigInteger('department_id');
            $table->string('role_name');
            $table->boolean('required')->default(true);
            $table->timestamps();

            $table->unique(['reimbursement_mode_id', 'step_no'], 'reim_mode_step_unique');
            $table->index(['department_id', 'role_name'], 'reim_mode_dept_role_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reimbursement_mode_approvals');
    }
};

