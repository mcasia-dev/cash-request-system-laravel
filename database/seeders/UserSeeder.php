<?php

namespace Database\Seeders;

use App\Enums\User\AccountStatus;
use App\Enums\User\Status;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $department = Department::query()->where('department_name', 'IT')->first()
            ?? Department::query()->first();

        if (!$department) {
            $this->command?->warn('UserSeeder skipped: no department record found.');

            return;
        }

        $admin = User::updateOrCreate(
            ['email' => 'it-admin@mcasiafoodtrade.ph'],
            [
                'first_name' => 'IT',
                'middle_name' => null,
                'last_name' => 'Administrator',
                'position' => 'System Administrator',
                'contact_number' => '09123456789',
                'department_id' => $department->id,
                'password' => Hash::make('@Password2026!'),
                'email_verified_at' => now(),
                'account_status' => AccountStatus::ACTIVE->value,
                'status' => Status::APPROVED->value,
                'review_at' => now(),
                'reason_for_rejection' => null,
            ]
        );

        $admin->forceFill([
            'review_by' => $admin->id,
        ])->save();

        $admin->syncRoles(['super_admin']);

        $this->command?->info('Admin user seeded');
    }
}
