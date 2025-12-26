<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Team;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Determine whether user can view project.
     */
    public function view(User $user, Project $project): bool
    {
        $team = $project->team;

        $isTeamMember = $user->id === $team->user_id ||
                        $team->users()->where('users.id', $user->id)->exists();

        if (! $isTeamMember) {
            return false;
        }

        if (! $project->access_restricted) {
            return true;
        }

        return $project->members->contains($user->id);
    }

    /**
     * Determine whether user can create projects.
     */
    public function create(User $user, Team $team): bool
    {
        return $user->id === $team->user_id ||
               $team->users()->where('users.id', $user->id)->exists();
    }

    /**
     * Determine whether user can update project.
     */
    public function update(User $user, Project $project): bool
    {
        $team = $project->team;

        if ($user->id === $team->user_id) {
            return true;
        }

        return $team->users()->where('users.id', $user->id)->where('team_members.role', 'admin')->exists();
    }

    /**
     * Determine whether user can delete project.
     */
    public function delete(User $user, Project $project): bool
    {
        $team = $project->team;

        if ($user->id === $team->user_id) {
            return true;
        }

        return $team->users()->where('users.id', $user->id)->where('team_members.role', 'admin')->exists();
    }
}
