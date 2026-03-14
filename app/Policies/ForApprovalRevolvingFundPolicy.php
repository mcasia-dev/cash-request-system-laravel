<?php

namespace App\Policies;

use App\Models\RevolvingFund\ForApprovalRevolvingFund;
use App\Models\User;

class ForApprovalRevolvingFundPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any ForApprovalRevolvingFund');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ForApprovalRevolvingFund $forapprovalrevolvingfund): bool
    {
        return $user->checkPermissionTo('view ForApprovalRevolvingFund');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create ForApprovalRevolvingFund');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ForApprovalRevolvingFund $forapprovalrevolvingfund): bool
    {
        return $user->checkPermissionTo('update ForApprovalRevolvingFund');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ForApprovalRevolvingFund $forapprovalrevolvingfund): bool
    {
        return $user->checkPermissionTo('delete ForApprovalRevolvingFund');
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('delete-any ForApprovalRevolvingFund');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ForApprovalRevolvingFund $forapprovalrevolvingfund): bool
    {
        return $user->checkPermissionTo('restore ForApprovalRevolvingFund');
    }

    /**
     * Determine whether the user can restore any models.
     */
    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('restore-any ForApprovalRevolvingFund');
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, ForApprovalRevolvingFund $forapprovalrevolvingfund): bool
    {
        return $user->checkPermissionTo('replicate ForApprovalRevolvingFund');
    }

    /**
     * Determine whether the user can reorder the models.
     */
    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('reorder ForApprovalRevolvingFund');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ForApprovalRevolvingFund $forapprovalrevolvingfund): bool
    {
        return $user->checkPermissionTo('force-delete ForApprovalRevolvingFund');
    }

    /**
     * Determine whether the user can permanently delete any models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('force-delete-any ForApprovalRevolvingFund');
    }
}
