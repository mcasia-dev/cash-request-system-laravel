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
            $table->unsignedInteger('revolving_fund_mode_of_transfer_id')->nullable()->after('user_id');
            $table->unsignedInteger('revolving_fund_purpose_id')->nullable()->after('revolving_fund_mode_of_transfer_id');
            $table->string('area_of_assignment')->nullable()->after('revolving_fund_purpose_id');
            $table->json('field_work_assignment')->nullable()->after('area_of_assignment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('revolving_funds', function (Blueprint $table) {
            $table->dropColumn(['revolving_fund_mode_of_transfer_id', 'revolving_fund_purpose_id', 'area_of_assignment', 'field_work_assignment']);
        });
    }
};
