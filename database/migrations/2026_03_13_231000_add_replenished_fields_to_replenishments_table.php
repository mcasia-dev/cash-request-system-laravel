<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('replenishments', function (Blueprint $table) {
            $table->unsignedBigInteger('replenished_by')
                ->nullable()
                ->after('reviewed_at');
            $table->dateTime('replenished_at')
                ->nullable()
                ->after('replenished_by');
        });

        DB::statement("ALTER TABLE replenishments MODIFY COLUMN status ENUM('pending', 'returned', 'approved', 'rejected', 'replenished') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE replenishments SET status = 'approved' WHERE status = 'replenished'");
        DB::statement("ALTER TABLE replenishments MODIFY COLUMN status ENUM('pending', 'returned', 'approved', 'rejected') NOT NULL DEFAULT 'pending'");

        Schema::table('replenishments', function (Blueprint $table) {
            $table->dropColumn([
                'replenished_by',
                'replenished_at',
            ]);
        });
    }
};

