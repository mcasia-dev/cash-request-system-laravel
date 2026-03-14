<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\Reimbursement\ForAccountingVerification;
use App\Models\User;

class ForAccountingVerificationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any ForAccountingVerification');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ForAccountingVerification $foraccountingverification): bool
    {
        return $user->checkPermissionTo('view ForAccountingVerification');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create ForAccountingVerification');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ForAccountingVerification $foraccountingverification): bool
    {
        return $user->checkPermissionTo('update ForAccountingVerification');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ForAccountingVerification $foraccountingverification): bool
    {
        return $user->checkPermissionTo('delete ForAccountingVerification');
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('delete-any ForAccountingVerification');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ForAccountingVerification $foraccountingverification): bool
    {
        return $user->checkPermissionTo('restore ForAccountingVerification');
    }

    /**
     * Determine whether the user can restore any models.
     */
    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('restore-any ForAccountingVerification');
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, ForAccountingVerification $foraccountingverification): bool
    {
        return $user->checkPermissionTo('replicate ForAccountingVerification');
    }

    /**
     * Determine whether the user can reorder the models.
     */
    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('reorder ForAccountingVerification');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ForAccountingVerification $foraccountingverification): bool
    {
        return $user->checkPermissionTo('force-delete ForAccountingVerification');
    }

    /**
     * Determine whether the user can permanently delete any models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('force-delete-any ForAccountingVerification');
    }
}
