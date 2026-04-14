<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Database\Seeders\Models\PermissionSeeder;
use Database\Seeders\RevolvingFund\ModeOfTransferSeeder;
use Database\Seeders\RevolvingFund\PurposeSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            DepartmentSeeder::class,
            RoleSeeder::class,
            ApprovalRuleSeeder::class,
            PermissionSeeder::class,
            PurposeSeeder::class,
            ModeOfTransferSeeder::class,
        ]);
    }
}
