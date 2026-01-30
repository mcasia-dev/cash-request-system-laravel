<?php

namespace Database\Seeders;

use App\Enums\NatureOfRequestEnum;
use App\Models\CashRequest;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class SampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get departments
        $itDept = Department::where('department_name', 'IT')->first();
        $hrDept = Department::where('department_name', 'HR')->first();
        $financeDept = Department::where('department_name', 'Finance')->first();

        // Create users with different roles
        $adminUser = User::updateOrCreate([
            'email' => 'admin@example.com',
        ], [
            'name' => 'Admin User',
            'first_name' => 'Admin',
            'last_name' => 'User',
            'position' => 'System Administrator',
            'contact_number' => '09123456789',
            'department_id' => $itDept->id,
            'password' => bcrypt('password123'),
            'account_status' => 'active',
            'status' => 'approved',
        ]);

        // Assign super_admin role
        $adminUser->assignRole('super_admin');

        $staffUser1 = User::updateOrCreate([
            'email' => 'john.doe@example.com',
        ], [
            'name' => 'John Doe',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'position' => 'IT Specialist',
            'contact_number' => '09123456780',
            'department_id' => $itDept->id,
            'password' => bcrypt('password123'),
            'account_status' => 'active',
            'status' => 'approved',
        ]);

        $staffUser1->assignRole('staff');

        $staffUser2 = User::updateOrCreate([
            'email' => 'jane.smith@example.com',
        ], [
            'name' => 'Jane Smith',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'position' => 'HR Officer',
            'contact_number' => '09123456781',
            'department_id' => $hrDept->id,
            'password' => bcrypt('password123'),
            'account_status' => 'active',
            'status' => 'approved',
        ]);

        $staffUser2->assignRole('staff');

        $financeUser = User::updateOrCreate([
            'email' => 'finance@example.com',
        ], [
            'name' => 'Finance Officer',
            'first_name' => 'Finance',
            'last_name' => 'Officer',
            'position' => 'Finance Manager',
            'contact_number' => '09123456782',
            'department_id' => $financeDept->id,
            'password' => bcrypt('password123'),
            'account_status' => 'active',
            'status' => 'approved',
        ]);

        $financeUser->assignRole('admin');

        $this->command->info('Users created successfully!');

        // Create sample cash requests with different statuses
        $cashRequests = [
            // Pending request
            [
                'user_id' => $staffUser1->id,
                'activity_name' => 'Team Building Event',
                'activity_date' => now()->addDays(10)->format('Y-m-d'),
                'activity_venue' => 'Tagaytay Retreat Center',
                'purpose' => 'Annual team building and bonding activity for the IT department',
                'nature_of_request' => NatureOfRequestEnum::PETTY_CASH->value,
                'requesting_amount' => 1500.00,
                'nature_of_payment' => 'cash',
                'payee' => 'John Doe',
                'payment_to' => 'myself',
                'status' => 'pending',
                'due_date' => now()->addDays(11)->format('Y-m-d'),
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ],
            // Approved request
            [
                'user_id' => $staffUser2->id,
                'activity_name' => 'HR Training Workshop',
                'activity_date' => now()->addDays(5)->format('Y-m-d'),
                'activity_venue' => 'Manila Hotel Conference Room',
                'purpose' => 'Leadership training workshop for new hires',
                'nature_of_request' => NatureOfRequestEnum::CASH_ADVANCE->value,
                'requesting_amount' => 5000.00,
                'nature_of_payment' => 'bank_transfer',
                'payee' => 'Jane Smith',
                'payment_to' => 'myself',
                'bank_account_no' => '1234567890',
                'bank_name' => 'BDO',
                'account_type' => 'savings',
                'status' => 'approved',
                'due_date' => now()->addDays(12)->format('Y-m-d'),
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(1),
            ],
            // Released request
            [
                'user_id' => $staffUser1->id,
                'activity_name' => 'Client Meeting - ABC Corp',
                'activity_date' => now()->addDays(2)->format('Y-m-d'),
                'activity_venue' => 'Makati Business Center',
                'purpose' => 'Meeting with ABC Corp for project discussion',
                'nature_of_request' => NatureOfRequestEnum::CASH_ADVANCE->value,
                'requesting_amount' => 3000.00,
                'nature_of_payment' => 'bank_transfer',
                'payee' => 'John Doe',
                'payment_to' => 'myself',
                'bank_account_no' => '0987654321',
                'bank_name' => 'BPI',
                'account_type' => 'savings',
                'status' => 'released',
                'date_released' => now()->format('Y-m-d'),
                'due_date' => now()->addDays(9)->format('Y-m-d'),
                'created_at' => now()->subDays(7),
                'updated_at' => now()->subDays(3),
            ],
            // Liquidated request
            [
                'user_id' => $staffUser2->id,
                'activity_name' => 'Office Supplies Purchase',
                'activity_date' => now()->subDays(5)->format('Y-m-d'),
                'activity_venue' => 'SM Manila',
                'purpose' => 'Purchase of printer paper, ink, and other office supplies',
                'nature_of_request' => NatureOfRequestEnum::PETTY_CASH->value,
                'requesting_amount' => 1000.00,
                'nature_of_payment' => 'cash',
                'payee' => 'Jane Smith',
                'payment_to' => 'myself',
                'status' => 'liquidated',
                'date_released' => now()->subDays(6)->format('Y-m-d'),
                'date_liquidated' => now()->subDays(4)->format('Y-m-d'),
                'due_date' => now()->subDays(2)->format('Y-m-d'),
                'created_at' => now()->subDays(8),
                'updated_at' => now()->subDays(4),
            ],
            // Rejected request
            [
                'user_id' => $staffUser1->id,
                'activity_name' => 'Unapproved Conference',
                'activity_date' => now()->addDays(15)->format('Y-m-d'),
                'activity_venue' => 'Cebu City',
                'purpose' => 'Personal conference not related to company activities',
                'nature_of_request' => NatureOfRequestEnum::CASH_ADVANCE->value,
                'requesting_amount' => 10000.00,
                'nature_of_payment' => 'bank_transfer',
                'payee' => 'John Doe',
                'payment_to' => 'myself',
                'bank_account_no' => '1234567890',
                'bank_name' => 'BDO',
                'account_type' => 'savings',
                'status' => 'rejected',
                'reason_for_rejection' => 'Not aligned with company objectives',
                'due_date' => now()->addDays(16)->format('Y-m-d'),
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subHours(5),
            ],
        ];

        foreach ($cashRequests as $request) {
            $cashRequest = CashRequest::create($request);

            // Create activity log entries for each cash request
            $this->createActivityLog($cashRequest);
        }

        $this->command->info('Sample cash requests created successfully!');
        $this->command->info('Activity logs created successfully!');
        $this->command->info('');
        $this->command->info('=== Sample Login Credentials ===');
        $this->command->info('Admin: admin@example.com / password123');
        $this->command->info('Staff: john.doe@example.com / password123');
        $this->command->info('HR Staff: jane.smith@example.com / password123');
        $this->command->info('Finance: finance@example.com / password123');
    }

    /**
     * Create activity log entries for a cash request
     */
    private function createActivityLog(CashRequest $cashRequest): void
    {
        $user = $cashRequest->user;

        // Created activity
        activity()
            ->causedBy($user)
            ->performedOn($cashRequest)
            ->event('created')
            ->withProperties([
                'request_no' => $cashRequest->request_no,
                'activity_name' => $cashRequest->activity_name,
                'requesting_amount' => $cashRequest->requesting_amount,
                'status' => $cashRequest->status,
            ])
            ->log("Cash request '{$cashRequest->request_no}' was created by {$user->name}");

        // Status-based activities
        if ($cashRequest->status === 'approved' || in_array($cashRequest->status, ['released', 'liquidated'])) {
            activity()
                ->causedBy(User::whereEmail('finance@example.com')->first() ?? $user)
                ->performedOn($cashRequest)
                ->event('approved')
                ->withProperties([
                    'request_no' => $cashRequest->request_no,
                    'previous_status' => 'pending',
                    'new_status' => 'approved',
                ])
                ->log("Cash request '{$cashRequest->request_no}' was approved");
        }

        if ($cashRequest->status === 'released' || $cashRequest->status === 'liquidated') {
            activity()
                ->causedBy(User::whereEmail('finance@example.com')->first() ?? $user)
                ->performedOn($cashRequest)
                ->event('released')
                ->withProperties([
                    'request_no' => $cashRequest->request_no,
                    'previous_status' => 'approved',
                    'new_status' => 'released',
                    'date_released' => $cashRequest->date_released,
                ])
                ->log("Cash request '{$cashRequest->request_no}' was released");
        }

        if ($cashRequest->status === 'liquidated') {
            activity()
                ->causedBy($user)
                ->performedOn($cashRequest)
                ->event('liquidated')
                ->withProperties([
                    'request_no' => $cashRequest->request_no,
                    'previous_status' => 'released',
                    'new_status' => 'liquidated',
                    'date_liquidated' => $cashRequest->date_liquidated,
                ])
                ->log("Cash request '{$cashRequest->request_no}' was liquidated");
        }

        if ($cashRequest->status === 'rejected') {
            activity()
                ->causedBy(User::whereEmail('finance@example.com')->first() ?? $user)
                ->performedOn($cashRequest)
                ->event('rejected')
                ->withProperties([
                    'request_no' => $cashRequest->request_no,
                    'previous_status' => 'pending',
                    'new_status' => 'rejected',
                    'reason' => $cashRequest->reason_for_rejection,
                ])
                ->log("Cash request '{$cashRequest->request_no}' was rejected. Reason: {$cashRequest->reason_for_rejection}");
        }
    }
}
