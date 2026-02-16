<?php

namespace Database\Seeders;

use App\Enums\NatureOfRequestEnum;
use App\Models\ApprovalRule;
use Illuminate\Database\Seeder;

class ApprovalRuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->createRule(
            nature: NatureOfRequestEnum::PETTY_CASH->value,
            minAmount: 0,
            maxAmount: 1500,
            roles: ['department_head']
        );

        $this->createRule(
            nature: NatureOfRequestEnum::CASH_ADVANCE->value,
            minAmount: 0,
            maxAmount: 10000,
            roles: ['sales_channel_manager']
        );

        $this->createRule(
            nature: NatureOfRequestEnum::CASH_ADVANCE->value,
            minAmount: 10001,
            maxAmount: 50000,
            roles: ['national_sales_manager']
        );

        $this->createRule(
            nature: NatureOfRequestEnum::CASH_ADVANCE->value,
            minAmount: 50001,
            maxAmount: null,
            roles: ['department_head', 'president']
        );
    }

    /**
     * @param array<int, string> $roles
     */
    private function createRule(string $nature, float $minAmount, ?float $maxAmount, array $roles): void
    {
        $rule = ApprovalRule::query()->updateOrCreate(
            [
                'nature'     => $nature,
                'min_amount' => $minAmount,
                'max_amount' => $maxAmount,
            ],
            [
                'is_active' => true,
            ]
        );

        $rule->approvalRuleSteps()->delete();

        foreach ($roles as $role) {
            $rule->approvalRuleSteps()->create([
                'role_name'  => $role,
                'step_order' => 1,
            ]);
        }
    }
}
