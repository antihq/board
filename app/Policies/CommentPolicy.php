<?php

namespace App\Policies;

use App\Models\Card;
use App\Models\Comment;
use App\Models\User;

class CommentPolicy
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
    public function view(User $user, Comment $comment): bool
    {
        return $comment->card->board->team->members->contains($user)
            || $comment->card->board->team->user->is($user);
    }

    /**
     * Determine whether user can create models.
     */
    public function create(User $user, Card $card): bool
    {
        return $card->board->team->members->contains($user)
            || $card->board->team->user->is($user);
    }

    /**
     * Determine whether user can update the model.
     */
    public function update(User $user, Comment $comment): bool
    {
        return $this->view($user, $comment) && $comment->user->is($user);
    }

    /**
     * Determine whether user can delete the model.
     */
    public function delete(User $user, Comment $comment): bool
    {
        return $this->view($user, $comment) &&
            ($comment->user->is($user) || $comment->card->board->team->user->is($user));
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
