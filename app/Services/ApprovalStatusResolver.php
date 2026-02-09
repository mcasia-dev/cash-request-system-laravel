<?php
namespace App\Services;

use App\Enums\CashRequest\StatusRemarks;
use App\Models\User;

class ApprovalStatusResolver
{

    /**
     * Resolve the approval status remark based on the user's highest approval role.
     *
     * @param User $user
     * @return string
     */
    public static function approve(User $user): string
    {
        $rolePriority = match (true) {
            $user->can('can-approve-as-department-head')        => StatusRemarks::DEPARTMENT_HEAD_APPROVED_REQUEST->value,
            $user->can('can-approve-as-president')              => StatusRemarks::PRESIDENT_APPROVED_REQUEST->value,
            $user->can('can-approve-as-treasury-manager')       => StatusRemarks::TREASURY_MANAGER_APPROVED_REQUEST->value,
            $user->can('can-approve-as-treasury-supervisor')    => StatusRemarks::TREASURY_SUPERVISOR_APPROVED_REQUEST->value,
            $user->can('can-approve-as-sales-channel-manager')  => StatusRemarks::SALES_CHANNEL_MANAGER_APPROVED_REQUEST->value,
            $user->can('can-approve-as-national-sales-manager') => StatusRemarks::NATIONAL_SALES_MANAGER_APPROVED_REQUEST->value,
            default                                             => 'No permissions'
        };

        return $rolePriority;
    }

    /**
     * Resolve the rejection status remark based on the user's highest rejection role.
     *
     * @param User $user
     * @return string
     */
    public static function reject(User $user): string
    {
        $rolePriority = match (true) {
            $user->can('can-reject-as-department-head')        => StatusRemarks::DEPARTMENT_HEAD_REJECTED_REQUEST->value,
            $user->can('can-reject-as-president')              => StatusRemarks::PRESIDENT_REJECTED_REQUEST->value,
            $user->can('can-reject-as-treasury-manager')       => StatusRemarks::TREASURY_MANAGER_REJECTED_REQUEST->value,
            $user->can('can-reject-as-treasury-supervisor')    => StatusRemarks::TREASURY_SUPERVISOR_REJECTED_REQUEST->value,
            $user->can('can-reject-as-sales-channel-manager')  => StatusRemarks::SALES_CHANNEL_MANAGER_REJECTED_REQUEST->value,
            $user->can('can-reject-as-national-sales-manager') => StatusRemarks::NATIONAL_SALES_MANAGER_REJECTED_REQUEST->value,
            default                                            => 'No permissions'
        };

        return $rolePriority;
    }
}
