<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Card extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'description' => 'string',
        'postponed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function column()
    {
        return $this->belongsTo(Column::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function board()
    {
        return $this->belongsTo(Board::class);
    }

    public function assignees()
    {
        return $this->belongsToMany(User::class, 'card_assignments');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class)->oldest();
    }

    #[Scope]
    protected function postponed(Builder $query): void
    {
        $query->whereNotNull('postponed_at');
    }

    #[Scope]
    protected function notPostponed(Builder $query): void
    {
        $query->whereNull('postponed_at');
    }

    #[Scope]
    protected function completed(Builder $query): void
    {
        $query->whereNotNull('completed_at');
    }

    #[Scope]
    protected function notCompleted(Builder $query): void
    {
        $query->whereNull('completed_at');
    }

    public function isPostponed()
    {
        return ! is_null($this->postponed_at);
    }

    public function isCompleted()
    {
        return ! is_null($this->completed_at);
    }

    #[Scope]
    protected function opened(Builder $query): void
    {
        $query->whereNull('postponed_at')
            ->whereNull('completed_at')
            ->whereNull('column_id');
    }

    public function moveToColumn(Column $column, int $position): void
    {
        if ($this->board_id !== $column->board_id) {
            throw new \InvalidArgumentException('Cannot move card to a column in a different board');
        }

        DB::transaction(function () use ($column, $position) {
            $this->cleanupFromSource();
            $this->makeRoomInTarget($position, 'column', $column);
            $this->update([
                'column_id' => $column->id,
                'position' => $position,
                'postponed_at' => null,
                'completed_at' => null,
            ]);
        });
    }

    public function moveToOpened(int $position): void
    {
        DB::transaction(function () use ($position) {
            $this->cleanupFromSource();
            $this->makeRoomInTarget($position, 'opened');
            $this->update([
                'column_id' => null,
                'position' => $position,
                'postponed_at' => null,
                'completed_at' => null,
            ]);
        });
    }

    public function moveToPostponed(int $position): void
    {
        DB::transaction(function () use ($position) {
            $this->cleanupFromSource();
            $this->makeRoomInTarget($position, 'postponed');
            $this->update([
                'column_id' => null,
                'position' => $position,
                'postponed_at' => now(),
                'completed_at' => null,
            ]);
        });
    }

    public function moveToCompleted(int $position): void
    {
        DB::transaction(function () use ($position) {
            $this->cleanupFromSource();
            $this->makeRoomInTarget($position, 'completed');
            $this->update([
                'column_id' => null,
                'position' => $position,
                'postponed_at' => null,
                'completed_at' => now(),
            ]);
        });
    }

    public static function addToOpened(Board $board, string $title, int $userId): self
    {
        return DB::transaction(function () use ($board, $title, $userId) {
            $board->cards()
                ->opened()
                ->increment('position');

            return $board->cards()->create([
                'user_id' => $userId,
                'title' => $title,
                'position' => 0,
            ]);
        });
    }

    private function cleanupFromSource(): void
    {
        if ($this->column_id) {
            $this->board->cards()
                ->where('column_id', $this->column_id)
                ->where('position', '>', $this->position)
                ->decrement('position');

            return;
        }

        if ($this->isPostponed()) {
            $this->board->cards()
                ->postponed()
                ->where('position', '>', $this->position)
                ->decrement('position');

            return;
        }

        if ($this->isCompleted()) {
            $this->board->cards()
                ->completed()
                ->where('position', '>', $this->position)
                ->decrement('position');

            return;
        }

        // From opened state
        $this->board->cards()
            ->opened()
            ->where('position', '>', $this->position)
            ->decrement('position');
    }

    private function makeRoomInTarget(int $position, string $targetType, ?Column $column = null): void
    {
        $query = $this->board->cards();

        match ($targetType) {
            'column' => $query->where('column_id', $column->id),
            'opened' => $query->opened(),
            'postponed' => $query->postponed(),
            'completed' => $query->completed(),
        };

        $query->where('position', '>=', $position)->increment('position');
    }

    public function moveInto($column, $position)
    {
        // Early return if no change needed
        if ($this->column_id === $column->id && $this->position === $position) {
            return;
        }

        $oldColumnId = $this->column_id;
        $oldPosition = $this->position;
        $newColumnId = $column->id;

        // Moving to different column
        if ($oldColumnId !== $newColumnId) {
            return DB::transaction(function () use ($position, $oldColumnId, $oldPosition, $newColumnId) {
                Card::where('column_id', $newColumnId)
                    ->where('position', '>=', $position)
                    ->increment('position');

                Card::where('column_id', $oldColumnId)
                    ->where('position', '>', $oldPosition)
                    ->decrement('position');

                return $this->update([
                    'column_id' => $newColumnId,
                    'position' => $position,
                ]);
            });
        }

        // Reordering within same column
        return DB::transaction(function () use ($position, $oldColumnId, $oldPosition) {
            match (true) {
                $position > $oldPosition => Card::where('column_id', $oldColumnId)
                    ->where('position', '>', $oldPosition)
                    ->where('position', '<=', $position)
                    ->decrement('position'),

                $position < $oldPosition => Card::where('column_id', $oldColumnId)
                    ->where('position', '>=', $position)
                    ->where('position', '<', $oldPosition)
                    ->increment('position'),

                default => null,
            };

            return $this->update(['position' => $position]);
        });
    }
}
