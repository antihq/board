<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class)
            ->whereNull('completed_at')
            ->orderBy('prioritized_at', 'desc')
            ->orderBy('updated_at', 'desc');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
