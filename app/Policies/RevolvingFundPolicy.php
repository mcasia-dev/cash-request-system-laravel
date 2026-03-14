<?php

namespace App\Policies;

use App\Models\RevolvingFund\RevolvingFund;
use App\Models\User;

class RevolvingFundPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any RevolvingFund');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RevolvingFund $revolvingfund): bool
    {
        return $user->checkPermissionTo('view RevolvingFund');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create RevolvingFund');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RevolvingFund $revolvingfund): bool
    {
        return $user->checkPermissionTo('update RevolvingFund');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RevolvingFund $revolvingfund): bool
    {
        return $user->checkPermissionTo('delete RevolvingFund');
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('delete-any RevolvingFund');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, RevolvingFund $revolvingfund): bool
    {
        return $user->checkPermissionTo('restore RevolvingFund');
    }

    /**
     * Determine whether the user can restore any models.
     */
    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('restore-any RevolvingFund');
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, RevolvingFund $revolvingfund): bool
    {
        return $user->checkPermissionTo('replicate RevolvingFund');
    }

    /**
     * Determine whether the user can reorder the models.
     */
    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('reorder RevolvingFund');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, RevolvingFund $revolvingfund): bool
    {
        return $user->checkPermissionTo('force-delete RevolvingFund');
    }

    /**
     * Determine whether the user can permanently delete any models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('force-delete-any RevolvingFund');
    }
}
