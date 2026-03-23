<?php

namespace Database\Seeders\RevolvingFund;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PurposeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $purposes = [
            [
                'purpose' => 'Transportation',
                'is_published' => true
            ],
            [
                'purpose' => 'Meal',
                'is_published' => true
            ],
            [
                'purpose' => 'Accommodation',
                'is_published' => true
            ],
            [
                'purpose' => 'Occasional Gift',
                'is_published' => true
            ],
            [
                'purpose' => 'Others',
                'is_published' => true
            ],
        ];

        foreach ($purposes as $purpose) {
            DB::table('revolving_fund_purposes')->updateOrInsert(['purpose' => $purpose['purpose']], $purpose);
        }
    }
}
