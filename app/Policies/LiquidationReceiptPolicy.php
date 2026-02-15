<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\LiquidationReceipt;
use App\Models\User;

class LiquidationReceiptPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any LiquidationReceipt');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LiquidationReceipt $liquidationreceipt): bool
    {
        return $user->checkPermissionTo('view LiquidationReceipt');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create LiquidationReceipt');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, LiquidationReceipt $liquidationreceipt): bool
    {
        return $user->checkPermissionTo('update LiquidationReceipt');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LiquidationReceipt $liquidationreceipt): bool
    {
        return $user->checkPermissionTo('delete LiquidationReceipt');
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('delete-any LiquidationReceipt');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, LiquidationReceipt $liquidationreceipt): bool
    {
        return $user->checkPermissionTo('restore LiquidationReceipt');
    }

    /**
     * Determine whether the user can restore any models.
     */
    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('restore-any LiquidationReceipt');
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, LiquidationReceipt $liquidationreceipt): bool
    {
        return $user->checkPermissionTo('replicate LiquidationReceipt');
    }

    /**
     * Determine whether the user can reorder the models.
     */
    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('reorder LiquidationReceipt');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, LiquidationReceipt $liquidationreceipt): bool
    {
        return $user->checkPermissionTo('force-delete LiquidationReceipt');
    }

    /**
     * Determine whether the user can permanently delete any models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('force-delete-any LiquidationReceipt');
    }
}
