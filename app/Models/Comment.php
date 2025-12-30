<?php

namespace App\Models;

use App\Notifications\TaskCommented;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Tiptap\Editor;

class Comment extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'edited_at' => 'datetime',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    public function images()
    {
        return $this->hasMany(CommentImage::class);
    }

    /**
     * Attach an image to this comment.
     */
    public function attachImage(TemporaryUploadedFile $image): CommentImage
    {
        $path = $image->store('comment-images', 'public');

        return $this->images()->create([
            'user_id' => Auth::id(),
            'path' => $path,
        ]);
    }

    public function notifySubscribers(): void
    {
        $this->task->subscribers()->syncWithoutDetaching($this->user->id);

        $this->task->subscribers
            ->where('id', '!=', $this->user->id)
            ->each(
                fn ($subscriber) => $subscriber->notify(
                    new TaskCommented($this),
                ),
            );
    }

    /**
     * Delete the comment and touch the associated task.
     */
    public function delete()
    {
        foreach ($this->images as $image) {
            $image->delete();
        }

        parent::delete();
        $this->task->touch();
    }

    /**
     * Get the comment's content as safe HTML.
     */
    protected function content(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? $this->sanitizeHtml($value) : null,
            set: fn (string $value) => $this->attributes['content'] = $value,
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
