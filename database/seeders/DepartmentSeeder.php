<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            [
                'department_name' => 'IT',
                'added_by' => null,
            ],
            [
                'department_name' => 'HRAD',
                'added_by' => null,
            ],
            [
                'department_name' => 'Customer Service',
                'added_by' => null,
            ],
            [
                'department_name' => 'Warehouse',
                'added_by' => null,
            ],
            [
                'department_name' => 'Logistics',
                'added_by' => null,
            ],
            [
                'department_name' => 'Demand Planning',
                'added_by' => null,
            ],
            [
                'department_name' => 'Quality Assurance',
                'added_by' => null,
            ],
            [
                'department_name' => 'Sales-Retail',
                'added_by' => null,
            ],
            [
                'department_name' => 'Sales-FSy',
                'added_by' => null,
            ],
            [
                'department_name' => 'Sales-Non Food',
                'added_by' => null,
            ],
            [
                'department_name' => 'Sales-Beverage',
                'added_by' => null,
            ],
            [
                'department_name' => 'Sales-Frozen',
                'added_by' => null,
            ],
            [
                'department_name' => 'Accounting',
                'added_by' => null,
            ],
            [
                'department_name' => 'Treasury',
                'added_by' => null,
            ],
            [
                'department_name' => 'Credit and Collections',
                'added_by' => null,
            ],
            [
                'department_name' => 'Finance',
                'added_by' => null,
            ],
            [
                'department_name' => 'Purchasing',
                'added_by' => null,
            ],
            [
                'department_name' => 'Business Dev/Office of the President',
                'added_by' => null,
            ],
            [
                'department_name' => 'Culinary Solutions',
                'added_by' => null,
            ],
            [
                'department_name' => 'General Admin Services & Production',
                'added_by' => null,
            ],
            [
                'department_name' => 'Retail-Marketing',
                'added_by' => null,
            ],
            [
                'department_name' => 'Technical Services',
                'added_by' => null,
            ],
        ];

        foreach ($departments as $department) {
            DB::table('departments')->updateOrInsert(['department_name' => $department['department_name']], $department);
        }
    }
}
