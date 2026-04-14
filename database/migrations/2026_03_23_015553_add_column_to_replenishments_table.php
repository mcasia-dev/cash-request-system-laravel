<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('replenishments', function (Blueprint $table) {
            $table->decimal('amount_to_reimburse', 15, 2)->nullable()->after('amount_to_deduct');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('replenishments', function (Blueprint $table) {
            $table->dropColumn('amount_to_reimburse');
        });
    }
};
