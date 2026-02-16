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
        Schema::table('users', function (Blueprint $table) {
            $table->string('control_no')->nullable()->after('id');
            $table->string('first_name')->after('name');
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('last_name')->after('middle_name');
            $table->string('position')->nullable()->after('last_name');
            $table->string('contact_number')->nullable()->after('email_verified_at');
            $table->string('signature_number')->nullable()->after('contact_number');
            $table->unsignedBigInteger('department_id')->nullable()->after('contact_number');
            $table->enum('account_status', ['active', 'blocked', 'suspended'])->nullable()->after('signature_number')->default('active');
            $table->enum('status', ['pending', 'approved', 'disapproved'])->default('pending')->after('account_status');
            $table->unsignedBigInteger('review_by')->nullable()->after('account_status')->comment('The one who reviewed the account. He/she can approved or disapproved the account.');
            $table->datetime('review_at')->nullable()->after('review_by')->comment('The date and time when the account was reviewed.');
            $table->text('reason_for_rejection')->nullable()->after('review_at')->comment('The reason why the account was disapproved.');
        });

        // Add unique constraint after table is created
        Schema::table('users', function (Blueprint $table) {
            $table->unique('control_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['control_no']);
            $table->dropColumn([
                'control_no',
                'first_name',
                'middle_name',
                'last_name',
                'position',
                'contact_number',
                'signature_number',
                'department_id',
                'account_status',
                'status',
                'review_by',
                'review_at',
                'reason_for_rejection',
            ]);
        });
    }
};
