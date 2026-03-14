<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\Reimbursement\ReimbursementItem;
use App\Models\User;

class ReimbursementItemPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any ReimbursementItem');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ReimbursementItem $reimbursementitem): bool
    {
        return $user->checkPermissionTo('view ReimbursementItem');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create ReimbursementItem');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ReimbursementItem $reimbursementitem): bool
    {
        return $user->checkPermissionTo('update ReimbursementItem');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ReimbursementItem $reimbursementitem): bool
    {
        return $user->checkPermissionTo('delete ReimbursementItem');
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('delete-any ReimbursementItem');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ReimbursementItem $reimbursementitem): bool
    {
        return $user->checkPermissionTo('restore ReimbursementItem');
    }

    /**
     * Determine whether the user can restore any models.
     */
    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('restore-any ReimbursementItem');
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, ReimbursementItem $reimbursementitem): bool
    {
        return $user->checkPermissionTo('replicate ReimbursementItem');
    }

    /**
     * Determine whether the user can reorder the models.
     */
    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('reorder ReimbursementItem');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ReimbursementItem $reimbursementitem): bool
    {
        return $user->checkPermissionTo('force-delete ReimbursementItem');
    }

    /**
     * Determine whether the user can permanently delete any models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('force-delete-any ReimbursementItem');
    }
}
