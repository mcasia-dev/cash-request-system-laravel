<?php

namespace App\Policies;

use App\Models\RevolvingFund\RevolvingFundApprovalRule;
use App\Models\User;

class RevolvingFundApprovalRulePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any RevolvingFundApprovalRule');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RevolvingFundApprovalRule $revolvingfundapprovalrule): bool
    {
        return $user->checkPermissionTo('view RevolvingFundApprovalRule');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create RevolvingFundApprovalRule');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RevolvingFundApprovalRule $revolvingfundapprovalrule): bool
    {
        return $user->checkPermissionTo('update RevolvingFundApprovalRule');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RevolvingFundApprovalRule $revolvingfundapprovalrule): bool
    {
        return $user->checkPermissionTo('delete RevolvingFundApprovalRule');
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('delete-any RevolvingFundApprovalRule');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, RevolvingFundApprovalRule $revolvingfundapprovalrule): bool
    {
        return $user->checkPermissionTo('restore RevolvingFundApprovalRule');
    }

    /**
     * Determine whether the user can restore any models.
     */
    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('restore-any RevolvingFundApprovalRule');
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, RevolvingFundApprovalRule $revolvingfundapprovalrule): bool
    {
        return $user->checkPermissionTo('replicate RevolvingFundApprovalRule');
    }

    /**
     * Determine whether the user can reorder the models.
     */
    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('reorder RevolvingFundApprovalRule');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, RevolvingFundApprovalRule $revolvingfundapprovalrule): bool
    {
        return $user->checkPermissionTo('force-delete RevolvingFundApprovalRule');
    }

    /**
     * Determine whether the user can permanently delete any models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('force-delete-any RevolvingFundApprovalRule');
    }
}
