<?php

namespace App\Policies;

use App\Models\RevolvingFund\ReplenishmentItem;
use App\Models\User;

class ReplenishmentItemPolicy
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
    public function view(User $user, ReplenishmentItem $replenishmentitem): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create ReplenishmentItem');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ReplenishmentItem $replenishmentitem): bool
    {
        return $user->checkPermissionTo('update ReplenishmentItem');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ReplenishmentItem $replenishmentitem): bool
    {
        return $user->checkPermissionTo('delete ReplenishmentItem');
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('delete-any ReplenishmentItem');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ReplenishmentItem $replenishmentitem): bool
    {
        return $user->checkPermissionTo('restore ReplenishmentItem');
    }

    /**
     * Determine whether the user can restore any models.
     */
    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('restore-any ReplenishmentItem');
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, ReplenishmentItem $replenishmentitem): bool
    {
        return $user->checkPermissionTo('replicate ReplenishmentItem');
    }

    /**
     * Determine whether the user can reorder the models.
     */
    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('reorder ReplenishmentItem');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ReplenishmentItem $replenishmentitem): bool
    {
        return $user->checkPermissionTo('force-delete ReplenishmentItem');
    }

    /**
     * Determine whether the user can permanently delete any models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('force-delete-any ReplenishmentItem');
    }
}
