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
                'department_name' => 'HR',
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
                'department_name' => 'Logistic',
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
                'department_name' => 'Sales Retail',
                'department_head' => 'Juan Dela Cruz',
                'added_by'        => null,
            ],
            [
                'department_name' => 'Sales Food Service',
                'department_head' => 'Juan Dela Cruz',
                'added_by'        => null,
            ],
            [
                'department_name' => 'Sales Non-Food',
                'department_head' => 'Juan Dela Cruz',
                'added_by'        => null,
            ],
            [
                'department_name' => 'Sales Beverages',
                'department_head' => 'Juan Dela Cruz',
                'added_by'        => null,
            ],
            [
                'department_name' => 'Sales Frozen',
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
                'department_name' => 'Office of the President',
                'department_head' => 'Juan Dela Cruz',
                'added_by'        => null,
            ],
        ];

        foreach ($departments as $department) {
            DB::table('departments')->updateOrInsert(['department_name' => $department['department_name']], $department);
        }
    }
}
