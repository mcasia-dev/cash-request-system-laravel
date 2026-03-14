<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\Reimbursement\ForReleasing;
use App\Models\User;

class ForReleasingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any ForReleasing');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ForReleasing $forreleasing): bool
    {
        return $user->checkPermissionTo('view ForReleasing');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create ForReleasing');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ForReleasing $forreleasing): bool
    {
        return $user->checkPermissionTo('update ForReleasing');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ForReleasing $forreleasing): bool
    {
        return $user->checkPermissionTo('delete ForReleasing');
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('delete-any ForReleasing');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ForReleasing $forreleasing): bool
    {
        return $user->checkPermissionTo('restore ForReleasing');
    }

    /**
     * Determine whether the user can restore any models.
     */
    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('restore-any ForReleasing');
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, ForReleasing $forreleasing): bool
    {
        return $user->checkPermissionTo('replicate ForReleasing');
    }

    /**
     * Determine whether the user can reorder the models.
     */
    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('reorder ForReleasing');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ForReleasing $forreleasing): bool
    {
        return $user->checkPermissionTo('force-delete ForReleasing');
    }

    /**
     * Determine whether the user can permanently delete any models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('force-delete-any ForReleasing');
    }
}
