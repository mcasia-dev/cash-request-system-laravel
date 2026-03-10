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
        Schema::table('for_liquidations', function (Blueprint $table) {
            $table->boolean('is_approved_by_treasury_manager')->default(false)->after('is_override');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('for_liquidations', function (Blueprint $table) {
            $table->dropColumn('is_approved_by_treasury_manager');
        });
    }
};
