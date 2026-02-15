<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\ApprovalRuleStep;
use App\Models\User;

class ApprovalRuleStepPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any ApprovalRuleStep');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ApprovalRuleStep $approvalrulestep): bool
    {
        return $user->checkPermissionTo('view ApprovalRuleStep');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create ApprovalRuleStep');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ApprovalRuleStep $approvalrulestep): bool
    {
        return $user->checkPermissionTo('update ApprovalRuleStep');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ApprovalRuleStep $approvalrulestep): bool
    {
        return $user->checkPermissionTo('delete ApprovalRuleStep');
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('delete-any ApprovalRuleStep');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ApprovalRuleStep $approvalrulestep): bool
    {
        return $user->checkPermissionTo('restore ApprovalRuleStep');
    }

    /**
     * Determine whether the user can restore any models.
     */
    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('restore-any ApprovalRuleStep');
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, ApprovalRuleStep $approvalrulestep): bool
    {
        return $user->checkPermissionTo('replicate ApprovalRuleStep');
    }

    /**
     * Determine whether the user can reorder the models.
     */
    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('reorder ApprovalRuleStep');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ApprovalRuleStep $approvalrulestep): bool
    {
        return $user->checkPermissionTo('force-delete ApprovalRuleStep');
    }

    /**
     * Determine whether the user can permanently delete any models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('force-delete-any ApprovalRuleStep');
    }
}
