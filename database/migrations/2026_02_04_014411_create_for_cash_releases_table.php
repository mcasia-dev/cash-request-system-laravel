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
        Schema::create('for_cash_releases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cash_request_id');
            $table->unsignedBigInteger('released_by')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->text('remarks')->nullable();
            $table->date('releasing_date')->nullable();
            $table->time('releasing_time_from')->nullable();
            $table->time('releasing_time_to')->nullable();
            $table->datetime('date_processed')->nullable();
            $table->datetime('date_released')->nullable();
            $table->datetime('date_edited')->nullable();
            $table->unsignedBigInteger('edited_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('for_cash_releases');
    }
};
