<?php

namespace App\Models;

use App\Notifications\TaskClosed;
use App\Notifications\TaskCompleted;
use App\Notifications\TaskReopened;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
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
        return $this->belongsTo(User::class, 'user_id');
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

    public function completedChecklistItems()
    {
        return $this->checklistItems()->completed();
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function assignees()
    {
        return $this->belongsToMany(User::class, 'task_assignments');
    }

    public function subscribers()
    {
        return $this->belongsToMany(User::class, 'task_subscriptions');
    }

    public function savers()
    {
        return $this->belongsToMany(User::class, 'saved_tasks')->withPivot('team_id');
    }

    public function images()
    {
        return $this->hasMany(TaskImage::class)->latest();
    }

    public function attachImage(TemporaryUploadedFile $image): TaskImage
    {
        $path = $image->store('task-images', 'public');

        return $this->images()->create([
            'user_id' => Auth::id(),
            'path' => $path,
        ]);
    }

    public function addChecklistItem(string $content): ChecklistItem
    {
        $item = $this->checklistItems()->create([
            'content' => $content,
            'completed' => false,
        ]);

        $this->touch();

        return $item;
    }

    public function addComment(string $content, ?User $user = null): Comment
    {
        $user = $user ?? Auth::user();

        $comment = $this->comments()->create([
            'user_id' => $user->id,
            'content' => $content,
        ]);

        $this->touch();

        $comment->notifySubscribers();

        return $comment;
    }

    public function addTag(string $name): Tag
    {
        $tag = $this->team->tags()->create([
            'name' => $name,
        ]);

        $this->tags()->attach($tag->id);

        $this->touch();

        return $tag;
    }

    public function reopen(User $user): void
    {
        if ($this->isOpen()) {
            return;
        }

        $this->update([
            'completed_at' => null,
            'completed_by' => null,
            'closed_at' => null,
            'closed_by' => null,
            'reopened_at' => now(),
            'reopened_by' => $user->id,
        ]);

        $this->notifyReopened($user);
    }

    public function complete(User $user): void
    {
        if ($this->isCompleted()) {
            return;
        }

        $this->update([
            'completed_at' => now(),
            'completed_by' => $user->id,
            'closed_at' => null,
            'closed_by' => null,
            'reopened_at' => null,
            'reopened_by' => null,
        ]);

        $this->notifyCompleted($user);
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
    public function needsClose(): bool
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
    public function wasClosed(): bool
    {
        return ! is_null($this->closed_at);
    }

    /**
     * Auto-close this task.
     */
    public function autoClose(): void
    {
        if (! $this->needsClose()) {
            return;
        }

        $this->update([
            'closed_at' => now(),
            'closed_by' => null,
        ]);
    }

    public function moveToPending(User $user): void
    {
        if ($this->isOpen() && $this->section_id === null) {
            return;
        }

        $this->reopen($user);

        $this->update([
            'section_id' => null,
            'section_moved_at' => null,
            'section_moved_by' => null,
        ]);
    }

    public function moveToSection(Section $section, User $user): void
    {
        if (! $this->isOpen()) {
            $this->reopen($user);
        }

        $this->update([
            'section_id' => $section->id,
            'section_moved_at' => now(),
            'section_moved_by' => $user->id,
        ]);
    }

    public function close(User $user): void
    {
        if ($this->isClosed()) {
            return;
        }

        $this->update([
            'closed_at' => now(),
            'closed_by' => $user->id,
            'completed_at' => null,
            'completed_by' => null,
            'reopened_at' => null,
            'reopened_by' => null,
        ]);

        $this->notifyClosed($user);
    }

    public function isPending(): bool
    {
        return $this->section_id === null && $this->completed_at === null && $this->closed_at === null;
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public function isClosed(): bool
    {
        return $this->closed_at !== null;
    }

    public function isOpen(): bool
    {
        return $this->completed_at === null && $this->closed_at === null;
    }

    public function isInSection(Section $section): bool
    {
        return $this->section_id === $section->id;
    }

    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->whereNull('completed_at')->whereNull('section_id');
    }

    #[Scope]
    protected function completed(Builder $query): void
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
    protected function closed(Builder $query): void
    {
        $query->whereNotNull('closed_at');
    }

    #[Scope]
    protected function notClosed(Builder $query): void
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

    private function notifyClosed(User $user): void
    {
        $this->subscribers
            ->where('id', '!=', $user->id)
            ->each(fn ($subscriber) => $subscriber->notify(new TaskClosed($this)));
    }

    private function notifyReopened(User $user): void
    {
        $this->subscribers
            ->where('id', '!=', $user->id)
            ->each(fn ($subscriber) => $subscriber->notify(new TaskReopened($this)));
    }

    private function notifyCompleted(User $user): void
    {
        $this->subscribers
            ->where('id', '!=', $user->id)
            ->each(fn ($subscriber) => $subscriber->notify(new TaskCompleted($this)));
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
