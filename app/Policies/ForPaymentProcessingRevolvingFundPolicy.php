<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\RevolvingFund\ForPaymentProcessingRevolvingFund;
use App\Models\User;

class ForPaymentProcessingRevolvingFundPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any ForPaymentProcessingRevolvingFund');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ForPaymentProcessingRevolvingFund $forpaymentprocessingrevolvingfund): bool
    {
        return $user->checkPermissionTo('view ForPaymentProcessingRevolvingFund');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create ForPaymentProcessingRevolvingFund');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ForPaymentProcessingRevolvingFund $forpaymentprocessingrevolvingfund): bool
    {
        return $user->checkPermissionTo('update ForPaymentProcessingRevolvingFund');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ForPaymentProcessingRevolvingFund $forpaymentprocessingrevolvingfund): bool
    {
        return $user->checkPermissionTo('delete ForPaymentProcessingRevolvingFund');
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('delete-any ForPaymentProcessingRevolvingFund');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ForPaymentProcessingRevolvingFund $forpaymentprocessingrevolvingfund): bool
    {
        return $user->checkPermissionTo('restore ForPaymentProcessingRevolvingFund');
    }

    /**
     * Determine whether the user can restore any models.
     */
    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('restore-any ForPaymentProcessingRevolvingFund');
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, ForPaymentProcessingRevolvingFund $forpaymentprocessingrevolvingfund): bool
    {
        return $user->checkPermissionTo('replicate ForPaymentProcessingRevolvingFund');
    }

    /**
     * Determine whether the user can reorder the models.
     */
    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('reorder ForPaymentProcessingRevolvingFund');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ForPaymentProcessingRevolvingFund $forpaymentprocessingrevolvingfund): bool
    {
        return $user->checkPermissionTo('force-delete ForPaymentProcessingRevolvingFund');
    }

    /**
     * Determine whether the user can permanently delete any models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('force-delete-any ForPaymentProcessingRevolvingFund');
    }
}
