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
            $table->decimal('amount_to_return', 15, 2)
                ->default(0)
                ->after('total_amount');
            $table->decimal('amount_to_deduct', 15, 2)
                ->default(0)
                ->after('amount_to_return');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('replenishments', function (Blueprint $table) {
            $table->dropColumn([
                'amount_to_return',
                'amount_to_deduct',
            ]);
        });
    }
};

