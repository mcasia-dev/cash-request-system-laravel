<?php

namespace App\Policies;

use App\Models\RevolvingFund\ForApprovalReplenishment;
use App\Models\User;

class ForApprovalReplenishmentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any ForApprovalReplenishment');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ForApprovalReplenishment $forapprovalreplenishment): bool
    {
        return $user->checkPermissionTo('view ForApprovalReplenishment');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create ForApprovalReplenishment');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ForApprovalReplenishment $forapprovalreplenishment): bool
    {
        return $user->checkPermissionTo('update ForApprovalReplenishment');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ForApprovalReplenishment $forapprovalreplenishment): bool
    {
        return $user->checkPermissionTo('delete ForApprovalReplenishment');
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('delete-any ForApprovalReplenishment');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ForApprovalReplenishment $forapprovalreplenishment): bool
    {
        return $user->checkPermissionTo('restore ForApprovalReplenishment');
    }

    /**
     * Determine whether the user can restore any models.
     */
    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('restore-any ForApprovalReplenishment');
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, ForApprovalReplenishment $forapprovalreplenishment): bool
    {
        return $user->checkPermissionTo('replicate ForApprovalReplenishment');
    }

    /**
     * Determine whether the user can reorder the models.
     */
    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('reorder ForApprovalReplenishment');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ForApprovalReplenishment $forapprovalreplenishment): bool
    {
        return $user->checkPermissionTo('force-delete ForApprovalReplenishment');
    }

    /**
     * Determine whether the user can permanently delete any models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('force-delete-any ForApprovalReplenishment');
    }
}
