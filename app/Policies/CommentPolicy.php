<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    public function delete(User $user, Comment $comment): bool
    {
        $team = $comment->task->team;

        $isOwner = $user->id === $team->user_id;
        $isCreator = $user->id === $comment->user_id;
        $isAdmin = $team->users()->where('users.id', $user->id)->where('team_members.role', 'admin')->exists();

        return $isOwner || $isCreator || $isAdmin;
    }
}
