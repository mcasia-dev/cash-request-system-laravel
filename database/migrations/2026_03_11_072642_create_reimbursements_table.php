<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reimbursements', function (Blueprint $table) {
            $table->id();
            $table->string('reimbursement_no')->unique();
            $table->datetime('reimbursement_date');
            $table->unsignedBigInteger('payee_id');
            $table->text('purpose')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('mode_of_transfer')->nullable();
            $table->enum('status', ['pending', 'in progress', 'approved', 'rejected', 'cancelled', 'released'])->nullable()->default('pending');
            $table->string('status_remarks')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->datetime('approved_at')->nullable();
            $table->unsignedBigInteger('checked_by')->nullable();
            $table->datetime('checked_at')->nullable();
            $table->unsignedBigInteger('released_by')->nullable();
            $table->datetime('released_at')->nullable();
            $table->datetime('cash_received_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reimbursements');
    }
};
