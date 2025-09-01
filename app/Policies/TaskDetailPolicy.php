<?php

namespace App\Policies;

use App\Models\TaskDetail;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TaskDetailPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TaskDetail $taskDetail): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TaskDetail $taskDetail): bool
    {

        return $user->id === $taskDetail->user_id || $user->role->name === 'superadmin';

    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TaskDetail $taskDetail): bool
    {
        return (int) ($taskDetail->score ?? 0) <= 0;
    }
    public function deleteAny(User $user): bool
    {
        return true;
    }
    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TaskDetail $taskDetail): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TaskDetail $taskDetail): bool
    {
        return false;
    }
}
