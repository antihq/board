<?php

namespace App\Policies;

use App\Models\User;

class TaskPolicy
{
    /**
     * Determine whether user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->current_team_id !== null;
    }

    /**
     * Determine whether user can view the model.
     */
    public function view(User $user, Task $task): bool
    {
        return $task->project->team->members->contains($user)
            || $task->project->team->user->is($user);
    }

    /**
     * Determine whether user can create models.
     */
    public function create(User $user): bool
    {
        return $user->current_team_id !== null;
    }

    /**
     * Determine whether user can update the model.
     */
    public function update(User $user, Task $task): bool
    {
        return $this->view($user, $task);
    }

    /**
     * Determine whether user can delete the model.
     */
    public function delete(User $user, Task $task): bool
    {
        return $this->view($user, $task);
    }

    /**
     * Determine whether user can assign the model.
     */
    public function assign(User $user, Task $task): bool
    {
        return $this->view($user, $task);
    }

    /**
     * Determine whether user can unassign the model.
     */
    public function unassign(User $user, Task $task): bool
    {
        return $this->view($user, $task);
    }

    /**
     * Determine whether user can restore the model.
     */
    public function restore(): bool
    {
        return false;
    }

    /**
     * Determine whether user can permanently delete the model.
     */
    public function forceDelete(): bool
    {
        return false;
    }
}
