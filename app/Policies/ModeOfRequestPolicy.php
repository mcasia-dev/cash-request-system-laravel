<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\Reimbursement\ModeOfRequest;
use App\Models\User;

class ModeOfRequestPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any ModeOfRequest');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ModeOfRequest $modeofrequest): bool
    {
        return $user->checkPermissionTo('view ModeOfRequest');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create ModeOfRequest');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ModeOfRequest $modeofrequest): bool
    {
        return $user->checkPermissionTo('update ModeOfRequest');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ModeOfRequest $modeofrequest): bool
    {
        return $user->checkPermissionTo('delete ModeOfRequest');
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('delete-any ModeOfRequest');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ModeOfRequest $modeofrequest): bool
    {
        return $user->checkPermissionTo('restore ModeOfRequest');
    }

    /**
     * Determine whether the user can restore any models.
     */
    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('restore-any ModeOfRequest');
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, ModeOfRequest $modeofrequest): bool
    {
        return $user->checkPermissionTo('replicate ModeOfRequest');
    }

    /**
     * Determine whether the user can reorder the models.
     */
    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('reorder ModeOfRequest');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ModeOfRequest $modeofrequest): bool
    {
        return $user->checkPermissionTo('force-delete ModeOfRequest');
    }

    /**
     * Determine whether the user can permanently delete any models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('force-delete-any ModeOfRequest');
    }
}
