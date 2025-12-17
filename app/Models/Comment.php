<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Tiptap\Editor;

class Comment extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
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
