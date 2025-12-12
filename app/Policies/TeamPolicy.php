<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    public function __construct()
    {
        //
    }

    /**
     * Create a new policy instance.
     */
    /**
     * Determine whether the user can create teams.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the team.
     */
    public function update(User $user, Team $team): bool
    {
        return $team->user->is($user);
    }

    /**
     * Determine whether the user can switch to the given team.
     */
    public function switch(User $user, Team $team): bool
    {
        return $user->teams->contains($team)
            || $team->members->contains($user);
    }

    /**
     * Determine whether the user can manage members of the team.
     */
    public function manageMembers(User $user, Team $team): bool
    {
        return $team->user->is($user);
    }
}
