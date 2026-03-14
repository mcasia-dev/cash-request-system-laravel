<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\Reimbursement\ForApprovalReimbursement;
use App\Models\User;

class ForApprovalReimbursementPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any ForApprovalReimbursement');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ForApprovalReimbursement $forapprovalreimbursement): bool
    {
        return $user->checkPermissionTo('view ForApprovalReimbursement');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create ForApprovalReimbursement');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ForApprovalReimbursement $forapprovalreimbursement): bool
    {
        return $user->checkPermissionTo('update ForApprovalReimbursement');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ForApprovalReimbursement $forapprovalreimbursement): bool
    {
        return $user->checkPermissionTo('delete ForApprovalReimbursement');
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('delete-any ForApprovalReimbursement');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ForApprovalReimbursement $forapprovalreimbursement): bool
    {
        return $user->checkPermissionTo('restore ForApprovalReimbursement');
    }

    /**
     * Determine whether the user can restore any models.
     */
    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('restore-any ForApprovalReimbursement');
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, ForApprovalReimbursement $forapprovalreimbursement): bool
    {
        return $user->checkPermissionTo('replicate ForApprovalReimbursement');
    }

    /**
     * Determine whether the user can reorder the models.
     */
    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('reorder ForApprovalReimbursement');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ForApprovalReimbursement $forapprovalreimbursement): bool
    {
        return $user->checkPermissionTo('force-delete ForApprovalReimbursement');
    }

    /**
     * Determine whether the user can permanently delete any models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('force-delete-any ForApprovalReimbursement');
    }
}
