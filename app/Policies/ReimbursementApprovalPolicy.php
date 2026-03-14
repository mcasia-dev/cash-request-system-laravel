<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\Reimbursement\ReimbursementApproval;
use App\Models\User;

class ReimbursementApprovalPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any ReimbursementApproval');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ReimbursementApproval $reimbursementapproval): bool
    {
        return $user->checkPermissionTo('view ReimbursementApproval');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create ReimbursementApproval');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ReimbursementApproval $reimbursementapproval): bool
    {
        return $user->checkPermissionTo('update ReimbursementApproval');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ReimbursementApproval $reimbursementapproval): bool
    {
        return $user->checkPermissionTo('delete ReimbursementApproval');
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('delete-any ReimbursementApproval');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ReimbursementApproval $reimbursementapproval): bool
    {
        return $user->checkPermissionTo('restore ReimbursementApproval');
    }

    /**
     * Determine whether the user can restore any models.
     */
    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('restore-any ReimbursementApproval');
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, ReimbursementApproval $reimbursementapproval): bool
    {
        return $user->checkPermissionTo('replicate ReimbursementApproval');
    }

    /**
     * Determine whether the user can reorder the models.
     */
    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('reorder ReimbursementApproval');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ReimbursementApproval $reimbursementapproval): bool
    {
        return $user->checkPermissionTo('force-delete ReimbursementApproval');
    }

    /**
     * Determine whether the user can permanently delete any models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('force-delete-any ReimbursementApproval');
    }
}
