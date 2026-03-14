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
            if (Schema::hasColumn('revolving_funds', 'position')) {
                $table->dropColumn('position');
            }

            if (!Schema::hasColumn('revolving_funds', 'fund_code')) {
                $table->string('fund_code')->unique()->after('id');
            }

            if (
                Schema::hasColumn('revolving_funds', 'amount')
                && !Schema::hasColumn('revolving_funds', 'initial_amount')
            ) {
                $table->renameColumn('amount', 'initial_amount');
            }

            if (!Schema::hasColumn('revolving_funds', 'remaining_amount')) {
                $table->decimal('remaining_amount', 15, 2)->nullable()->after('initial_amount');
            }

            if (!Schema::hasColumn('revolving_funds', 'added_by')) {
                $table->unsignedBigInteger('added_by')->nullable()->after('remaining_amount');
            }

            if (!Schema::hasColumn('revolving_funds', 'status')) {
                $table->enum('status', ['pending', 'in progress', 'approved', 'rejected', 'replenished'])->nullable()->after('remaining_amount')->default('pending');
            }

            if (!Schema::hasColumn('revolving_funds', 'status_remarks')) {
                $table->string('status_remarks')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('revolving_funds', function (Blueprint $table) {
            if (
                Schema::hasColumn('revolving_funds', 'initial_amount')
                && !Schema::hasColumn('revolving_funds', 'amount')
            ) {
                $table->renameColumn('initial_amount', 'amount');
            }

            if (!Schema::hasColumn('revolving_funds', 'position')) {
                $table->string('position')->after('amount');
            }

            $columnsToDrop = array_values(array_filter([
                Schema::hasColumn('revolving_funds', 'fund_code') ? 'fund_code' : null,
                Schema::hasColumn('revolving_funds', 'remaining_amount') ? 'remaining_amount' : null,
                Schema::hasColumn('revolving_funds', 'added_by') ? 'added_by' : null,
                Schema::hasColumn('revolving_funds', 'status') ? 'status' : null,
                Schema::hasColumn('revolving_funds', 'status_remarks') ? 'status_remarks' : null,
            ]));

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
