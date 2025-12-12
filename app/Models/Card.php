<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Card extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'description' => 'string',
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
        return $this->column()->first()->board();
    }

    public function moveInto($column, $position)
    {
        if ($this->column_id === $column->id && $this->position === $position) {
            return;
        }

        $oldColumnId = $this->column_id;
        $oldPosition = $this->position;
        $newColumnId = $column->id;

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
