<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\Reimbursement\ForPaymentProcessing;
use App\Models\User;

class ForPaymentProcessingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any ForPaymentProcessing');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ForPaymentProcessing $forpaymentprocessing): bool
    {
        return $user->checkPermissionTo('view ForPaymentProcessing');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create ForPaymentProcessing');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ForPaymentProcessing $forpaymentprocessing): bool
    {
        return $user->checkPermissionTo('update ForPaymentProcessing');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ForPaymentProcessing $forpaymentprocessing): bool
    {
        return $user->checkPermissionTo('delete ForPaymentProcessing');
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('delete-any ForPaymentProcessing');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ForPaymentProcessing $forpaymentprocessing): bool
    {
        return $user->checkPermissionTo('restore ForPaymentProcessing');
    }

    /**
     * Determine whether the user can restore any models.
     */
    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('restore-any ForPaymentProcessing');
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, ForPaymentProcessing $forpaymentprocessing): bool
    {
        return $user->checkPermissionTo('replicate ForPaymentProcessing');
    }

    /**
     * Determine whether the user can reorder the models.
     */
    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('reorder ForPaymentProcessing');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ForPaymentProcessing $forpaymentprocessing): bool
    {
        return $user->checkPermissionTo('force-delete ForPaymentProcessing');
    }

    /**
     * Determine whether the user can permanently delete any models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('force-delete-any ForPaymentProcessing');
    }
}
