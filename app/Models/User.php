<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\OneTimePasswords\Models\Concerns\HasOneTimePasswords;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasOneTimePasswords, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    /**
     * Teams where the user is a member (not owner).
     */
    public function memberTeams()
    {
        return $this->belongsToMany(Team::class, 'team_user');
    }

    /**
     * All teams the user owns or is a member of (unique).
     */
    public function allTeams()
    {
        return $this->teams
            ->merge($this->memberTeams)
            ->unique('id')
            ->values();
    }

    /**
     * Get the user's current team.
     */
    public function currentTeam()
    {
        if (is_null($this->current_team_id)) {
            $this->assignPersonalTeamAsCurrent();

            $this->fresh();
        }

        return $this->belongsTo(Team::class, 'current_team_id');
    }

    /**
     * Assign the user's personal team as their current team.
     */
    private function assignPersonalTeamAsCurrent(): void
    {
        tap($this->teams()->where('personal', true)->first(), function ($personalTeam) {
            if ($personalTeam) {
                $this->current_team_id = $personalTeam->id;
                $this->save();
            }
        });
    }

    /**
     * Switch the user's current team.
     */
    public function switchTeam(Team $team): void
    {
        $this->current_team_id = $team->id;

        $this->save();
    }
}
