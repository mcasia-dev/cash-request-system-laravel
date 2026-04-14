<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\RevolvingFundModeOfTransfer;
use App\Models\User;

class RevolvingFundModeOfTransferPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any RevolvingFundModeOfTransfer');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RevolvingFundModeOfTransfer $revolvingfundmodeoftransfer): bool
    {
        return $user->checkPermissionTo('view RevolvingFundModeOfTransfer');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create RevolvingFundModeOfTransfer');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RevolvingFundModeOfTransfer $revolvingfundmodeoftransfer): bool
    {
        return $user->checkPermissionTo('update RevolvingFundModeOfTransfer');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RevolvingFundModeOfTransfer $revolvingfundmodeoftransfer): bool
    {
        return $user->checkPermissionTo('delete RevolvingFundModeOfTransfer');
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('delete-any RevolvingFundModeOfTransfer');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, RevolvingFundModeOfTransfer $revolvingfundmodeoftransfer): bool
    {
        return $user->checkPermissionTo('restore RevolvingFundModeOfTransfer');
    }

    /**
     * Determine whether the user can restore any models.
     */
    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('restore-any RevolvingFundModeOfTransfer');
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, RevolvingFundModeOfTransfer $revolvingfundmodeoftransfer): bool
    {
        return $user->checkPermissionTo('replicate RevolvingFundModeOfTransfer');
    }

    /**
     * Determine whether the user can reorder the models.
     */
    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('reorder RevolvingFundModeOfTransfer');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, RevolvingFundModeOfTransfer $revolvingfundmodeoftransfer): bool
    {
        return $user->checkPermissionTo('force-delete RevolvingFundModeOfTransfer');
    }

    /**
     * Determine whether the user can permanently delete any models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('force-delete-any RevolvingFundModeOfTransfer');
    }
}
