<?php

namespace App\Policies;

use App\Models\RevolvingFund\Replenishment;
use App\Models\User;

class ReplenishmentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Replenishment $replenishment): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create Replenishment');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Replenishment $replenishment): bool
    {
        return $user->checkPermissionTo('update Replenishment');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Replenishment $replenishment): bool
    {
        return $user->checkPermissionTo('delete Replenishment');
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('delete-any Replenishment');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Replenishment $replenishment): bool
    {
        return $user->checkPermissionTo('restore Replenishment');
    }

    /**
     * Determine whether the user can restore any models.
     */
    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('restore-any Replenishment');
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, Replenishment $replenishment): bool
    {
        return $user->checkPermissionTo('replicate Replenishment');
    }

    /**
     * Determine whether the user can reorder the models.
     */
    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('reorder Replenishment');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Replenishment $replenishment): bool
    {
        return $user->checkPermissionTo('force-delete Replenishment');
    }

    /**
     * Determine whether the user can permanently delete any models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('force-delete-any Replenishment');
    }
}
