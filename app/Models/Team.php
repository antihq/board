<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Team extends Model
{
    /** @use HasFactory<\Database\Factories\TeamFactory> */
    use HasFactory;

    protected $guarded = [];

    public static function generateUniqueHandle(string $name): string
    {
        $baseHandle = Str::slug($name);
        $counter = 0;
        $handle = $baseHandle;

        do {
            $existingHandle = static::where('handle', $handle)->lockForUpdate()->first();
            if (! $existingHandle) {
                break;
            }
            $counter++;
            $handle = $baseHandle . '-' . $counter;
        } while ($counter < 100); // Prevent infinite loops

        return $handle;
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function tags()
    {
        return $this->hasMany(Tag::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'team_members')
            ->withTimestamps()
            ->withPivot('role');
    }

    public function allUsers()
    {
        $teamUsers = $this->users()->get();
        $owner = $this->owner;

        if (! $teamUsers->contains('id', $owner->id)) {
            $teamUsers = $teamUsers->push($owner);
        }

        return $teamUsers->sortBy('name')->values();
    }

    public function getRouteKeyName(): string
    {
        return 'handle';
    }

    public function regenerateInvitationCode(): void
    {
        $this->update([
            'invitation_code' => Str::random(8),
            'invitation_code_uses_count' => 0,
        ]);
    }

    protected function casts(): array
    {
        return [
            'personal' => 'boolean',
            'invitation_code_max_uses' => 'integer',
            'invitation_code_uses_count' => 'integer',
        ];
    }
}
