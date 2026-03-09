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
        Schema::table('for_cash_releases', function (Blueprint $table) {
            $table->tinyInteger('update_releasing_date_attempt')->default(0)->after('releasing_time_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('for_cash_releases', function (Blueprint $table) {
            $table->dropColumn('update_releasing_date_attempt');
        });
    }
};
