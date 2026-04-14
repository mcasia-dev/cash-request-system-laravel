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
        Schema::table('revolving_funds', function (Blueprint $table) {
            $table->string('other_purpose')->nullable()->after('field_work_assignment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('revolving_funds', function (Blueprint $table) {
            $table->dropColumn('other_purpose');
        });
    }
};
