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
        Schema::create('for_liquidations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cash_request_id');
            $table->integer('receipt_amount')->nullable();
            $table->text('remarks')->nullable();
            $table->integer('total_user')->nullable();
            $table->float('total_liquidated')->nullable();
            $table->float('total_change')->nullable();
            $table->float('missing_amount')->nullable();
            $table->integer('aging')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('for_liquidations');
    }
};
