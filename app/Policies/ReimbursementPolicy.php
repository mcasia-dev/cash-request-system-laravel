<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\Reimbursement\Reimbursement;
use App\Models\User;

class ReimbursementPolicy
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
    public function view(User $user, Reimbursement $reimbursement): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create Reimbursement');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Reimbursement $reimbursement): bool
    {
        return $user->checkPermissionTo('update Reimbursement');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Reimbursement $reimbursement): bool
    {
        return $user->checkPermissionTo('delete Reimbursement');
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('delete-any Reimbursement');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Reimbursement $reimbursement): bool
    {
        return $user->checkPermissionTo('restore Reimbursement');
    }

    /**
     * Determine whether the user can restore any models.
     */
    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('restore-any Reimbursement');
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, Reimbursement $reimbursement): bool
    {
        return $user->checkPermissionTo('replicate Reimbursement');
    }

    /**
     * Determine whether the user can reorder the models.
     */
    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('reorder Reimbursement');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Reimbursement $reimbursement): bool
    {
        return $user->checkPermissionTo('force-delete Reimbursement');
    }

    /**
     * Determine whether the user can permanently delete any models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('force-delete-any Reimbursement');
    }
}
