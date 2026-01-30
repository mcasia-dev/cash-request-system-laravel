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
                'department_name' => 'Finance',
                'department_head' => 'Juan Dela Cruz',
                'added_by'        => null,
            ],
            [
                'department_name' => 'Marketing',
                'department_head' => 'Ana Santos',
                'added_by'        => null,
            ],
            [
                'department_name' => 'Operations',
                'department_head' => 'Carlos Reyes',
                'added_by'        => null,
            ],
        ];

        foreach ($departments as $department) {
            DB::table('departments')->updateOrInsert(['department_name' => $department['department_name']], $department);
        }
    }
}
