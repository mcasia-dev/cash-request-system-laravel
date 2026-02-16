<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\ForLiquidation;
use App\Models\User;

class ForLiquidationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any ForLiquidation');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ForLiquidation $forliquidation): bool
    {
        return $user->checkPermissionTo('view ForLiquidation');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create ForLiquidation');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ForLiquidation $forliquidation): bool
    {
        return $user->checkPermissionTo('update ForLiquidation');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ForLiquidation $forliquidation): bool
    {
        return $user->checkPermissionTo('delete ForLiquidation');
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('delete-any ForLiquidation');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ForLiquidation $forliquidation): bool
    {
        return $user->checkPermissionTo('restore ForLiquidation');
    }

    /**
     * Determine whether the user can restore any models.
     */
    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('restore-any ForLiquidation');
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, ForLiquidation $forliquidation): bool
    {
        return $user->checkPermissionTo('replicate ForLiquidation');
    }

    /**
     * Determine whether the user can reorder the models.
     */
    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('reorder ForLiquidation');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ForLiquidation $forliquidation): bool
    {
        return $user->checkPermissionTo('force-delete ForLiquidation');
    }

    /**
     * Determine whether the user can permanently delete any models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('force-delete-any ForLiquidation');
    }
}
