<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_lists', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('requesting_amount');
            $table->text('rejection_remarks')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('activity_lists', function (Blueprint $table) {
            $table->dropColumn(['status', 'rejection_remarks']);
        });
    }
};
