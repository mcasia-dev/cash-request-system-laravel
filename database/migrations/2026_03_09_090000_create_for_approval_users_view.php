<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement(<<<SQL
            CREATE VIEW for_approval_users_view AS
            SELECT
                id,
                control_no,
                name,
                first_name,
                middle_name,
                last_name,
                position,
                email,
                email_verified_at,
                contact_number,
                signature_number,
                department_id,
                account_status,
                status,
                review_by,
                review_at,
                reason_for_rejection,
                remember_token,
                created_at,
                updated_at
            FROM users
            WHERE status = 'pending'
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS for_approval_users_view');
    }
};
