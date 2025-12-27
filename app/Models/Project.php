<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Project extends Model
{
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

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function sections()
    {
        return $this->hasMany(Section::class)->ordered();
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'project_members')->withTimestamps();
    }

    public function getRouteKeyName()
    {
        return 'handle';
    }

    public function addTask(string $title, User $user): Task
    {
        return DB::transaction(function () use ($title, $user) {
            $maxNumber = $this->tasks()->lockForUpdate()->max('number') ?? 0;

            $task = $this->tasks()->create([
                'team_id' => $this->team_id,
                'user_id' => $user->id,
                'title' => $title,
                'number' => $maxNumber + 1,
            ]);

            $task->subscribers()->attach($user->id);

            return $task;
        });
    }

    public function pendingTasks(): HasMany
    {
        return $this->tasks()
            ->pending()
            ->orderBy('prioritized_at', 'desc')
            ->orderBy('updated_at', 'desc');
    }

    protected function casts(): array
    {
        return [
            'access_restricted' => 'boolean',
        ];
    }
}
