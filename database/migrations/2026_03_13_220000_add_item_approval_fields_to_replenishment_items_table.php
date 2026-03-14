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
        Schema::table('replenishment_items', function (Blueprint $table) {
            $table->boolean('is_approved')
                ->nullable()
                ->after('amount');
            $table->text('approval_remarks')
                ->nullable()
                ->after('is_approved');
            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->after('approval_remarks');
            $table->dateTime('reviewed_at')
                ->nullable()
                ->after('reviewed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('replenishment_items', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn([
                'is_approved',
                'approval_remarks',
                'reviewed_by',
                'reviewed_at',
            ]);
        });
    }
};

