<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\RevolvingFund\ForReleasingRevolvingFund;
use App\Models\User;

class ForReleasingRevolvingFundPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any ForReleasingRevolvingFund');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ForReleasingRevolvingFund $forreleasingrevolvingfund): bool
    {
        return $user->checkPermissionTo('view ForReleasingRevolvingFund');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create ForReleasingRevolvingFund');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ForReleasingRevolvingFund $forreleasingrevolvingfund): bool
    {
        return $user->checkPermissionTo('update ForReleasingRevolvingFund');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ForReleasingRevolvingFund $forreleasingrevolvingfund): bool
    {
        return $user->checkPermissionTo('delete ForReleasingRevolvingFund');
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('delete-any ForReleasingRevolvingFund');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ForReleasingRevolvingFund $forreleasingrevolvingfund): bool
    {
        return $user->checkPermissionTo('restore ForReleasingRevolvingFund');
    }

    /**
     * Determine whether the user can restore any models.
     */
    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('restore-any ForReleasingRevolvingFund');
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, ForReleasingRevolvingFund $forreleasingrevolvingfund): bool
    {
        return $user->checkPermissionTo('replicate ForReleasingRevolvingFund');
    }

    /**
     * Determine whether the user can reorder the models.
     */
    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('reorder ForReleasingRevolvingFund');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ForReleasingRevolvingFund $forreleasingrevolvingfund): bool
    {
        return $user->checkPermissionTo('force-delete ForReleasingRevolvingFund');
    }

    /**
     * Determine whether the user can permanently delete any models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('force-delete-any ForReleasingRevolvingFund');
    }
}
