<?php

namespace App\Policies;

use App\Models\Card;
use App\Models\User;

class CardPolicy
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
    public function view(User $user, Card $card): bool
    {
        return $card->board->team->members->contains($user)
            || $card->board->team->user->is($user);
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
    public function update(User $user, Card $card): bool
    {
        return $this->view($user, $card);
    }

    /**
     * Determine whether user can delete the model.
     */
    public function delete(User $user, Card $card): bool
    {
        return $this->view($user, $card);
    }

    /**
     * Determine whether user can restore the model.
     */
    public function restore(User $user, Card $card): bool
    {
        return false;
    }

    /**
     * Determine whether user can permanently delete the model.
     */
    public function forceDelete(User $user, Card $card): bool
    {
        return false;
    }
}
