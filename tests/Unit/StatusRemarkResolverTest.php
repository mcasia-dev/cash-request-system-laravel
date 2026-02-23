<?php

namespace Tests\Unit;

use App\Enums\CashRequest\StatusRemarks;
use App\Models\User;
use App\Services\Remarks\StatusRemarkResolver;
use Tests\TestCase;

class StatusRemarkResolverTest extends TestCase
{
    public function test_approve_by_permissions_returns_configured_remark(): void
    {
        $resolver = new StatusRemarkResolver();

        $user = $this->getMockBuilder(User::class)
            ->onlyMethods(['can'])
            ->getMock();

        $user->method('can')
            ->willReturnCallback(fn(string $permission) => $permission === 'can-approve-as-finance-staff');

        $this->assertSame(
            StatusRemarks::FINANCE_DEPARTMENT_APPROVED_REQUEST->value,
            $resolver->approveByPermissions($user, 'finance')
        );
    }

    public function test_reject_by_permissions_falls_back_to_default(): void
    {
        $resolver = new StatusRemarkResolver();

        $user = $this->getMockBuilder(User::class)
            ->onlyMethods(['can'])
            ->getMock();

        $user->method('can')->willReturn(false);

        $this->assertSame('Rejected', $resolver->rejectByPermissions($user, 'finance'));
    }

    public function test_approve_by_permissions_or_no_permissions_uses_no_permissions_default(): void
    {
        $resolver = new StatusRemarkResolver();

        $user = $this->getMockBuilder(User::class)
            ->onlyMethods(['can'])
            ->getMock();

        $user->method('can')->willReturn(false);

        $this->assertSame('No permissions', $resolver->approveByPermissionsOrNoPermissions($user, 'approval'));
    }

    public function test_approve_by_role_maps_known_roles(): void
    {
        $resolver = new StatusRemarkResolver();

        $this->assertSame(
            StatusRemarks::TREASURY_MANAGER_APPROVED_REQUEST->value,
            $resolver->approveByRole('treasury_manager')
        );
    }
}
