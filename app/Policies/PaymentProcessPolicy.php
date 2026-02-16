<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\PaymentProcess;
use App\Models\User;

class PaymentProcessPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any PaymentProcess');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PaymentProcess $paymentprocess): bool
    {
        return $user->checkPermissionTo('view PaymentProcess');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create PaymentProcess');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PaymentProcess $paymentprocess): bool
    {
        return $user->checkPermissionTo('update PaymentProcess');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PaymentProcess $paymentprocess): bool
    {
        return $user->checkPermissionTo('delete PaymentProcess');
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('delete-any PaymentProcess');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PaymentProcess $paymentprocess): bool
    {
        return $user->checkPermissionTo('restore PaymentProcess');
    }

    /**
     * Determine whether the user can restore any models.
     */
    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('restore-any PaymentProcess');
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, PaymentProcess $paymentprocess): bool
    {
        return $user->checkPermissionTo('replicate PaymentProcess');
    }

    /**
     * Determine whether the user can reorder the models.
     */
    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('reorder PaymentProcess');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PaymentProcess $paymentprocess): bool
    {
        return $user->checkPermissionTo('force-delete PaymentProcess');
    }

    /**
     * Determine whether the user can permanently delete any models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('force-delete-any PaymentProcess');
    }
}
