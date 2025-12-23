<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    /**
     * Determine whether user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether user can view model.
     */
    public function view(User $user, Team $team): bool
    {
        return $user->id === $team->user_id ||
               $team->users()->where('users.id', $user->id)->exists();
    }

    /**
     * Determine whether user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether user can update model.
     */
    public function update(User $user, Team $team): bool
    {
        if ($user->id === $team->user_id) {
            return true;
        }

        return $team->users()->where('users.id', $user->id)->where('team_members.role', 'admin')->exists();
    }

    /**
     * Determine whether user can delete model.
     */
    public function delete(User $user, Team $team): bool
    {
        return false;
    }

    /**
     * Determine whether user can restore model.
     */
    public function restore(User $user, Team $team): bool
    {
        return false;
    }

    /**
     * Determine whether user can permanently delete model.
     */
    public function forceDelete(User $user, Team $team): bool
    {
        return false;
    }
}
