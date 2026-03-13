<?php

namespace Tests\Unit;

use App\Enums\CashRequest\DisbursementType;
use App\Filament\Resources\PaymentProcessResource\Pages\ViewPaymentProcess;
use Tests\TestCase;

class ViewPaymentProcessDisbursementTest extends TestCase
{
    public function test_it_forces_payroll_when_mode_of_transfer_is_for_deposit(): void
    {
        $page = new ViewPaymentProcess();
        $record = (object) [
            'mode_of_transfer' => 'for deposit',
            'disbursement_type' => DisbursementType::CHECK->value,
        ];

        $resolvedType = $this->invokePrivate($page, 'resolveDisbursementType', [$record]);

        $this->assertSame(DisbursementType::PAYROLL->value, $resolvedType);
    }

    public function test_it_treats_for_deposit_with_underscore_as_for_deposit_mode(): void
    {
        $page = new ViewPaymentProcess();
        $record = (object) [
            'mode_of_transfer' => 'for_deposit',
            'disbursement_type' => null,
        ];

        $isForDeposit = $this->invokePrivate($page, 'isForDepositModeOfTransfer', [$record]);

        $this->assertTrue($isForDeposit);
    }

    public function test_it_keeps_selected_disbursement_type_for_non_deposit_mode(): void
    {
        $page = new ViewPaymentProcess();
        $record = (object) [
            'mode_of_transfer' => 'for pickup',
            'disbursement_type' => DisbursementType::CHECK->value,
        ];

        $resolvedType = $this->invokePrivate($page, 'resolveDisbursementType', [$record]);

        $this->assertSame(DisbursementType::CHECK->value, $resolvedType);
    }

    private function invokePrivate(object $target, string $method, array $arguments = []): mixed
    {
        $reflection = new \ReflectionClass($target);
        $methodRef = $reflection->getMethod($method);
        $methodRef->setAccessible(true);

        return $methodRef->invokeArgs($target, $arguments);
    }
}
