<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles
        $roles = [
            [
                'name'       => 'super_admin',
                'guard_name' => 'web',
            ],
            [
                'name'       => 'department_head',
                'guard_name' => 'web',
            ],
            [
                'name'       => 'president',
                'guard_name' => 'web',
            ],
            [
                'name'       => 'sales_channel_manager',
                'guard_name' => 'web',
            ],
            [
                'name'       => 'national_sales_manager',
                'guard_name' => 'web',
            ],
            [
                'name'       => 'treasury_manager',
                'guard_name' => 'web',
            ],
            [
                'name'       => 'treasury_supervisor',
                'guard_name' => 'web',
            ],
            [
                'name'       => 'treasury_staff',
                'guard_name' => 'web',
            ],
            [
                'name'       => 'finance_staff',
                'guard_name' => 'web',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name'], 'guard_name' => $role['guard_name']],
                $role
            );
        }

        $this->command->info('Roles created successfully!');
    }
}
