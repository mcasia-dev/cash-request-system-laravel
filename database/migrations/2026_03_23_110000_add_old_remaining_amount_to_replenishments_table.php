<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('replenishments', function (Blueprint $table): void {
            $table->decimal('old_remaining_amount', 15, 2)
                ->nullable()
                ->after('initial_amount');
        });
    }

    public function down(): void
    {
        Schema::table('replenishments', function (Blueprint $table): void {
            $table->dropColumn('old_remaining_amount');
        });
    }
};
