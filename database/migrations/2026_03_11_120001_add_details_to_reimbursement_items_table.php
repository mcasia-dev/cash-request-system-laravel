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
        Schema::table('reimbursement_items', function (Blueprint $table) {
            $table->string('item_name')->nullable()->after('reimbursement_id');
            $table->text('description')->nullable()->after('item_name');
            $table->decimal('amount', 15, 2)->default(0)->after('description');
            $table->string('attachment')->nullable()->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reimbursement_items', function (Blueprint $table) {
            $table->dropColumn([
                'item_name',
                'description',
                'amount',
                'attachment',
            ]);
        });
    }
};
