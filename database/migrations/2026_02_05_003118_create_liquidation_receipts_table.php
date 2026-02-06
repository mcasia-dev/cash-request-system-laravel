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
        Schema::create('liquidation_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('liquidation_id')->references('id')->on('for_liquidations')->onDelete('cascade');
            $table->float('receipt_amount')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('liquidation_receipts');
    }
};
