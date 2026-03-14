<?php

namespace App\Policies;

use App\Models\RevolvingFund\RequestDiscussion;
use App\Models\User;

class RequestDiscussionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any RequestDiscussion');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RequestDiscussion $requestdiscussion): bool
    {
        return $user->checkPermissionTo('view RequestDiscussion');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create RequestDiscussion');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RequestDiscussion $requestdiscussion): bool
    {
        return $user->checkPermissionTo('update RequestDiscussion');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RequestDiscussion $requestdiscussion): bool
    {
        return $user->checkPermissionTo('delete RequestDiscussion');
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('delete-any RequestDiscussion');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, RequestDiscussion $requestdiscussion): bool
    {
        return $user->checkPermissionTo('restore RequestDiscussion');
    }

    /**
     * Determine whether the user can restore any models.
     */
    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('restore-any RequestDiscussion');
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, RequestDiscussion $requestdiscussion): bool
    {
        return $user->checkPermissionTo('replicate RequestDiscussion');
    }

    /**
     * Determine whether the user can reorder the models.
     */
    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('reorder RequestDiscussion');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, RequestDiscussion $requestdiscussion): bool
    {
        return $user->checkPermissionTo('force-delete RequestDiscussion');
    }

    /**
     * Determine whether the user can permanently delete any models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('force-delete-any RequestDiscussion');
    }
}
