<?php
namespace App\Services;

use App\Models\User;
use App\Services\Remarks\StatusRemarkResolver;

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
        return app(StatusRemarkResolver::class)->approveByPermissionsOrNoPermissions($user, 'approval');
    }

    /**
     * Resolve the rejection status remark based on the user's highest rejection role.
     *
     * @param User $user
     * @return string
     */
    public static function reject(User $user): string
    {
        return app(StatusRemarkResolver::class)->rejectByPermissionsOrNoPermissions($user, 'approval');
    }
}
