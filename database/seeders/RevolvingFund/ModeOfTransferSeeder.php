<?php

namespace Database\Seeders\RevolvingFund;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModeOfTransferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mode_of_transfers = [
            [
                'name' => 'Company Issued Car',
                'is_published' => true,
            ],
            [
                'name' => 'Public Transportation',
                'is_published' => true,
            ],
        ];

        foreach ($mode_of_transfers as $mode_of_transfer) {
            DB::table('revolving_fund_mode_of_transfers')->updateOrInsert(['name' => $mode_of_transfer['name']], $mode_of_transfer);
        }
    }
}
