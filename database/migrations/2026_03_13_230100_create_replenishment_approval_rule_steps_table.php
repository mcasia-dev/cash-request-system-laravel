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
        Schema::create('replenishment_approval_rule_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('replenishment_approval_rule_id');
            $table->string('role_name');
            $table->unsignedInteger('step_order')->default(1);
            $table->timestamps();

            $table->foreign('replenishment_approval_rule_id', 'rep_rule_steps_rule_id_fk')
                ->references('id')
                ->on('replenishment_approval_rules')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('replenishment_approval_rule_steps');
    }
};

