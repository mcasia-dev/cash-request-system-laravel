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
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->unsignedBigInteger('reimbursement_mode_id')
                ->nullable()
                ->after('payee_id');

            $table->index('reimbursement_mode_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->dropIndex(['reimbursement_mode_id']);
            $table->dropColumn('reimbursement_mode_id');
        });
    }
};

