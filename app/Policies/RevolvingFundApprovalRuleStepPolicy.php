<?php

namespace App\Policies;

use App\Models\RevolvingFund\RevolvingFundApprovalRuleStep;
use App\Models\User;

class RevolvingFundApprovalRuleStepPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any RevolvingFundApprovalRuleStep');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RevolvingFundApprovalRuleStep $revolvingfundapprovalrulestep): bool
    {
        return $user->checkPermissionTo('view RevolvingFundApprovalRuleStep');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create RevolvingFundApprovalRuleStep');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RevolvingFundApprovalRuleStep $revolvingfundapprovalrulestep): bool
    {
        return $user->checkPermissionTo('update RevolvingFundApprovalRuleStep');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RevolvingFundApprovalRuleStep $revolvingfundapprovalrulestep): bool
    {
        return $user->checkPermissionTo('delete RevolvingFundApprovalRuleStep');
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('delete-any RevolvingFundApprovalRuleStep');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, RevolvingFundApprovalRuleStep $revolvingfundapprovalrulestep): bool
    {
        return $user->checkPermissionTo('restore RevolvingFundApprovalRuleStep');
    }

    /**
     * Determine whether the user can restore any models.
     */
    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('restore-any RevolvingFundApprovalRuleStep');
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, RevolvingFundApprovalRuleStep $revolvingfundapprovalrulestep): bool
    {
        return $user->checkPermissionTo('replicate RevolvingFundApprovalRuleStep');
    }

    /**
     * Determine whether the user can reorder the models.
     */
    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('reorder RevolvingFundApprovalRuleStep');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, RevolvingFundApprovalRuleStep $revolvingfundapprovalrulestep): bool
    {
        return $user->checkPermissionTo('force-delete RevolvingFundApprovalRuleStep');
    }

    /**
     * Determine whether the user can permanently delete any models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('force-delete-any RevolvingFundApprovalRuleStep');
    }
}
