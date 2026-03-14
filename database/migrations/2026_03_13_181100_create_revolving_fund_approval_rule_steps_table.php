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
        Schema::create('revolving_fund_approval_rule_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('revolving_fund_approval_rule_id');
            $table->string('role_name');
            $table->unsignedInteger('step_order')->default(1);
            $table->timestamps();

            $table->foreign('revolving_fund_approval_rule_id', 'rf_rule_steps_rule_id_fk')
                ->references('id')
                ->on('revolving_fund_approval_rules')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revolving_fund_approval_rule_steps');
    }
};
