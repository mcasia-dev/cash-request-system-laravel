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
            $table->date('proposed_releasing_date')->nullable()->after('remarks');
            $table->time('proposed_releasing_time_from')->nullable()->after('proposed_releasing_date');
            $table->time('proposed_releasing_time_to')->nullable()->after('proposed_releasing_time_from');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('for_cash_releases', function (Blueprint $table) {
            $table->dropColumn(['proposed_releasing_date', 'proposed_releasing_time_from', 'proposed_releasing_time_to']);
        });
    }
};
