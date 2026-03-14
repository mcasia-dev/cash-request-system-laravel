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
        Schema::create('request_discussions', function (Blueprint $table) {
            $table->id();
            $table->morphs('discussable');
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->enum('type', ['submission', 'return', 'response'])->default('submission');
            $table->text('remarks');
            $table->timestamps();

            $table->index(['discussable_type', 'discussable_id', 'created_at'], 'req_discussion_lookup_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_discussions');
    }
};
