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
                'department_head' => 'Edwin Villa',
                'added_by'        => null,
            ],
            [
                'department_name' => 'HRAD',
                'department_head' => 'Maria Clara',
                'added_by'        => null,
            ],
            [
                'department_name' => 'Customer Service',
                'department_head' => 'Juan Dela Cruz',
                'added_by'        => null,
            ],
            [
                'department_name' => 'Warehouse',
                'department_head' => 'Juan Dela Cruz',
                'added_by'        => null,
            ],
            [
                'department_name' => 'Logistics',
                'department_head' => 'Juan Dela Cruz',
                'added_by'        => null,
            ],
            [
                'department_name' => 'Demand Planning',
                'department_head' => 'Juan Dela Cruz',
                'added_by'        => null,
            ],
            [
                'department_name' => 'Quality Assurance',
                'department_head' => 'Juan Dela Cruz',
                'added_by'        => null,
            ],
            [
                'department_name' => 'Sales-Retail',
                'department_head' => 'Juan Dela Cruz',
                'added_by'        => null,
            ],
            [
                'department_name' => 'Sales-FSy',
                'department_head' => 'Juan Dela Cruz',
                'added_by'        => null,
            ],
            [
                'department_name' => 'Sales-Non Food',
                'department_head' => 'Juan Dela Cruz',
                'added_by'        => null,
            ],
            [
                'department_name' => 'Sales-Beverage',
                'department_head' => 'Juan Dela Cruz',
                'added_by'        => null,
            ],
            [
                'department_name' => 'Sales-Frozen',
                'department_head' => 'Juan Dela Cruz',
                'added_by'        => null,
            ],
            [
                'department_name' => 'Accounting',
                'department_head' => 'Juan Dela Cruz',
                'added_by'        => null,
            ],
            [
                'department_name' => 'Treasury',
                'department_head' => 'Juan Dela Cruz',
                'added_by'        => null,
            ],
            [
                'department_name' => 'Credit and Collections',
                'department_head' => 'Juan Dela Cruz',
                'added_by'        => null,
            ],
            [
                'department_name' => 'Finance',
                'department_head' => 'Juan Dela Cruz',
                'added_by'        => null,
            ],
            [
                'department_name' => 'Purchasing',
                'department_head' => 'Juan Dela Cruz',
                'added_by'        => null,
            ],
            [
                'department_name' => 'Business Dev/Office of the President',
                'department_head' => 'Juan Dela Cruz',
                'added_by'        => null,
            ],
            [
                'department_name' => 'Culinary Solutions',
                'department_head' => 'Juan Dela Cruz',
                'added_by'        => null,
            ],
            [
                'department_name' => 'General Admin Services & Production',
                'department_head' => 'Juan Dela Cruz',
                'added_by'        => null,
            ],
            [
                'department_name' => 'Retail-Marketing',
                'department_head' => 'Juan Dela Cruz',
                'added_by'        => null,
            ],
            [
                'department_name' => 'Technical Services',
                'department_head' => 'Juan Dela Cruz',
                'added_by'        => null,
            ],
        ];

        foreach ($departments as $department) {
            DB::table('departments')->updateOrInsert(['department_name' => $department['department_name']], $department);
        }
    }
}
