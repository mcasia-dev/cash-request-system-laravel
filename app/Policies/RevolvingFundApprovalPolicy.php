<?php

namespace App\Policies;

use App\Models\RevolvingFund\RevolvingFundApproval;
use App\Models\User;

class RevolvingFundApprovalPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any RevolvingFundApproval');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RevolvingFundApproval $revolvingfundapproval): bool
    {
        return $user->checkPermissionTo('view RevolvingFundApproval');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create RevolvingFundApproval');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RevolvingFundApproval $revolvingfundapproval): bool
    {
        return $user->checkPermissionTo('update RevolvingFundApproval');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RevolvingFundApproval $revolvingfundapproval): bool
    {
        return $user->checkPermissionTo('delete RevolvingFundApproval');
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('delete-any RevolvingFundApproval');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, RevolvingFundApproval $revolvingfundapproval): bool
    {
        return $user->checkPermissionTo('restore RevolvingFundApproval');
    }

    /**
     * Determine whether the user can restore any models.
     */
    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('restore-any RevolvingFundApproval');
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, RevolvingFundApproval $revolvingfundapproval): bool
    {
        return $user->checkPermissionTo('replicate RevolvingFundApproval');
    }

    /**
     * Determine whether the user can reorder the models.
     */
    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('reorder RevolvingFundApproval');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, RevolvingFundApproval $revolvingfundapproval): bool
    {
        return $user->checkPermissionTo('force-delete RevolvingFundApproval');
    }

    /**
     * Determine whether the user can permanently delete any models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('force-delete-any RevolvingFundApproval');
    }
}
