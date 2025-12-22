<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Tiptap\Editor;

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
        'prioritized_at' => 'datetime',
        'closed_at' => 'datetime',
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

    public function prioritizer()
    {
        return $this->belongsTo(User::class, 'prioritized_by');
    }

    public function closer()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class)->oldest();
    }

    public function checklistItems()
    {
        return $this->hasMany(ChecklistItem::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * Get the effective auto-close days for this task.
     */
    public function autoCloseDays(): ?int
    {
        return $this->project->auto_close_days ?? $this->team->auto_close_days;
    }

    /**
     * Check if this task should be auto-closed based on its last update.
     */
    public function needsAutoClose(): bool
    {
        if ($this->completed_at || $this->closed_at) {
            return false;
        }

        $autoCloseDays = $this->autoCloseDays();

        if (! $autoCloseDays) {
            return false;
        }

        return $this->updated_at->lt(now()->subDays($autoCloseDays));
    }

    /**
     * Check if this task was auto-closed.
     */
    public function wasAutoClosed(): bool
    {
        return ! is_null($this->closed_at);
    }

    /**
     * Auto-close this task.
     */
    public function autoClose(): void
    {
        if (! $this->needsAutoClose()) {
            return;
        }

        $this->update([
            'completed_at' => now(),
            'closed_at' => now(),
            'closed_by' => null,
        ]);
    }

    #[Scope]
    protected function inbox(Builder $query): void
    {
        $query->whereNull('completed_at')->whereNull('section_id');
    }

    #[Scope]
    protected function done(Builder $query): void
    {
        $query->whereNotNull('completed_at');
    }

    #[Scope]
    protected function sectioned(Builder $query): void
    {
        $query->whereNotNull('section_id');
    }

    #[Scope]
    protected function priority(Builder $query): void
    {
        $query->whereNotNull('prioritized_at');
    }

    #[Scope]
    protected function autoClosed(Builder $query): void
    {
        $query->whereNotNull('closed_at');
    }

    #[Scope]
    protected function notAutoClosed(Builder $query): void
    {
        $query->whereNull('closed_at');
    }

    /**
     * Scope to get tasks that need to be auto-closed.
     */
    #[Scope]
    protected function shouldAutoClose(Builder $query): void
    {
        $query->whereNull('completed_at')
            ->whereNull('closed_at');
    }

    /**
     * Get the task's description as safe HTML.
     */
    protected function description(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? $this->sanitizeHtml($value) : null,
            set: fn (string $value) => $this->attributes['description'] = $value,
        );
    }

    /**
     * Sanitize HTML using Tiptap.
     */
    private function sanitizeHtml(string $html): string
    {
        $editor = new Editor;

        return $editor->sanitize($html);
    }
}
