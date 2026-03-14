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
        Schema::table('replenishments', function (Blueprint $table) {
            $table->enum('status', ['pending', 'returned', 'approved', 'rejected'])
                ->default('pending')
                ->after('total_amount');
            $table->string('status_remarks')->nullable()->after('status');
            $table->text('reason_for_rejection')->nullable()->after('status_remarks');
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('reason_for_rejection');
            $table->dateTime('reviewed_at')->nullable()->after('reviewed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('replenishments', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'status_remarks',
                'reason_for_rejection',
                'reviewed_by',
                'reviewed_at',
            ]);
        });
    }
};
