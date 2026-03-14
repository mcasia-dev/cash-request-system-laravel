<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\Reimbursement\ReimbursementModeApproval;
use App\Models\User;

class ReimbursementModeApprovalPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any ReimbursementModeApproval');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ReimbursementModeApproval $reimbursementmodeapproval): bool
    {
        return $user->checkPermissionTo('view ReimbursementModeApproval');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create ReimbursementModeApproval');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ReimbursementModeApproval $reimbursementmodeapproval): bool
    {
        return $user->checkPermissionTo('update ReimbursementModeApproval');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ReimbursementModeApproval $reimbursementmodeapproval): bool
    {
        return $user->checkPermissionTo('delete ReimbursementModeApproval');
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('delete-any ReimbursementModeApproval');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ReimbursementModeApproval $reimbursementmodeapproval): bool
    {
        return $user->checkPermissionTo('restore ReimbursementModeApproval');
    }

    /**
     * Determine whether the user can restore any models.
     */
    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('restore-any ReimbursementModeApproval');
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, ReimbursementModeApproval $reimbursementmodeapproval): bool
    {
        return $user->checkPermissionTo('replicate ReimbursementModeApproval');
    }

    /**
     * Determine whether the user can reorder the models.
     */
    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('reorder ReimbursementModeApproval');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ReimbursementModeApproval $reimbursementmodeapproval): bool
    {
        return $user->checkPermissionTo('force-delete ReimbursementModeApproval');
    }

    /**
     * Determine whether the user can permanently delete any models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('force-delete-any ReimbursementModeApproval');
    }
}
