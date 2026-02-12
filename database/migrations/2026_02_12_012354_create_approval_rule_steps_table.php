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
        Schema::create('approval_rule_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('approval_rule_id');
            $table->string('role_name')->nullable();
            $table->integer('step_order')->nullable()->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_rule_steps');
    }
};
