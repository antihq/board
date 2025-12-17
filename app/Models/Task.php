<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'completed_at' => 'datetime',
        'reopened_at' => 'datetime',
        'section_moved_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class);
    }

    public function completer()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function reopener()
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function sectionMover()
    {
        return $this->belongsTo(User::class, 'section_moved_by');
    }

    public function scopeInbox($query)
    {
        return $query->whereNull('completed_at')->whereNull('section_id');
    }

    public function scopeDone($query)
    {
        return $query->whereNotNull('completed_at');
    }

    public function scopeSectioned($query)
    {
        return $query->whereNotNull('section_id');
    }
}
