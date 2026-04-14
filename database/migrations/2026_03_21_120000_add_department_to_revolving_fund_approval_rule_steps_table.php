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
        Schema::table('revolving_fund_approval_rule_steps', function (Blueprint $table) {
            $table->foreignId('department_id')
                ->nullable()
                ->after('role_name')
                ->constrained('departments')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('revolving_fund_approval_rule_steps', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
        });
    }
};
