<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    public function getRouteKeyName()
    {
        return 'handle';
    }
}
