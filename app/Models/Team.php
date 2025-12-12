<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Cashier\Billable;

class Team extends Model
{
    /** @use HasFactory<\Database\Factories\TeamFactory> */
    use Billable, HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'personal' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function invitations()
    {
        return $this->hasMany(TeamInvitation::class);
    }

    public function boards()
    {
        return $this->hasMany(Board::class);
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'team_user');
    }

    public function addMember(User $user): void
    {
        $this->members()->attach($user->id);
    }

    public function removeMember(User $user): void
    {
        $this->members()->detach($user->id);

        if ($user->current_team_id === $this->id) {
            $user->current_team_id = null;
            $user->save();
        }
    }

    public function isMember(User $user): bool
    {
        return $this->members->contains($user);
    }

    public function inviteMember(string $email): TeamInvitation
    {
        return $this->invitations()->create([
            'email' => $email,
        ]);
    }

    public function stripeEmail()
    {
        return $this->user->email ?? null;
    }
}
