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
        Schema::create('activity_lists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('control_no')->nullable();
            $table->string('activity_name')->nullable();
            $table->date('activity_date')->nullable();
            $table->string('activity_venue')->nullable();
            $table->text('purpose')->nullable();
            $table->string('nature_of_request')->nullable();
            $table->integer('requesting_amount');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_lists');
    }
};
