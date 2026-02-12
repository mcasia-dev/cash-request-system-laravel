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
        Schema::create('cash_request_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cash_request_id');
            $table->integer('step_order')->nullable();
            $table->string('role_name')->nullable();
            $table->string('approved_by')->nullable();
            $table->enum('status', ['pending', 'approved', 'declined'])->default('pending');
            $table->datetime('acted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_request_approvals');
    }
};
