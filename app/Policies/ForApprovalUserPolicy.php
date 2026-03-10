<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\ForApprovalUser;
use App\Models\User;

class ForApprovalUserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any ForApprovalUser');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ForApprovalUser $forapprovaluser): bool
    {
        return $user->checkPermissionTo('view ForApprovalUser');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create ForApprovalUser');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ForApprovalUser $forapprovaluser): bool
    {
        return $user->checkPermissionTo('update ForApprovalUser');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ForApprovalUser $forapprovaluser): bool
    {
        return $user->checkPermissionTo('delete ForApprovalUser');
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('delete-any ForApprovalUser');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ForApprovalUser $forapprovaluser): bool
    {
        return $user->checkPermissionTo('restore ForApprovalUser');
    }

    /**
     * Determine whether the user can restore any models.
     */
    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('restore-any ForApprovalUser');
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, ForApprovalUser $forapprovaluser): bool
    {
        return $user->checkPermissionTo('replicate ForApprovalUser');
    }

    /**
     * Determine whether the user can reorder the models.
     */
    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('reorder ForApprovalUser');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ForApprovalUser $forapprovaluser): bool
    {
        return $user->checkPermissionTo('force-delete ForApprovalUser');
    }

    /**
     * Determine whether the user can permanently delete any models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('force-delete-any ForApprovalUser');
    }
}
