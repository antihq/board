<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Determine whether user can view task.
     */
    public function view(User $user, Task $task): bool
    {
        $team = $task->team;

        return $user->id === $team->user_id ||
               $team->users()->where('users.id', $user->id)->exists();
    }

    /**
     * Determine whether user can update task.
     */
    public function update(User $user, Task $task): bool
    {
        $team = $task->team;

        return $user->id === $team->user_id ||
               $team->users()->where('users.id', $user->id)->exists();
    }

    /**
     * Determine whether user can delete task.
     */
    public function delete(User $user, Task $task): bool
    {
        $team = $task->team;

        return $user->id === $team->user_id;
    }

    /**
     * Determine whether user can manage assignees (update task assignments).
     */
    public function manageAssignees(User $user, Task $task): bool
    {
        return $this->update($user, $task);
    }
}
