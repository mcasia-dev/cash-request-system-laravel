<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\ForCashRelease;
use App\Models\User;

class ForCashReleasePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any ForCashRelease');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ForCashRelease $forcashrelease): bool
    {
        return $user->checkPermissionTo('view ForCashRelease');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create ForCashRelease');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ForCashRelease $forcashrelease): bool
    {
        return $user->checkPermissionTo('update ForCashRelease');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ForCashRelease $forcashrelease): bool
    {
        return $user->checkPermissionTo('delete ForCashRelease');
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('delete-any ForCashRelease');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ForCashRelease $forcashrelease): bool
    {
        return $user->checkPermissionTo('restore ForCashRelease');
    }

    /**
     * Determine whether the user can restore any models.
     */
    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('restore-any ForCashRelease');
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, ForCashRelease $forcashrelease): bool
    {
        return $user->checkPermissionTo('replicate ForCashRelease');
    }

    /**
     * Determine whether the user can reorder the models.
     */
    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('reorder ForCashRelease');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ForCashRelease $forcashrelease): bool
    {
        return $user->checkPermissionTo('force-delete ForCashRelease');
    }

    /**
     * Determine whether the user can permanently delete any models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('force-delete-any ForCashRelease');
    }
}
