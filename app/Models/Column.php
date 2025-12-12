<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Column extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function board()
    {
        return $this->belongsTo(Board::class);
    }

    public function cards()
    {
        return $this->hasMany(Card::class)->orderBy('position');
    }

    public function shiftCardsDown(): void
    {
        $this->cards()->increment('position');
    }

    public function shiftCardsUp(int $fromPosition): void
    {
        $this->cards()
            ->where('position', '>', $fromPosition)
            ->decrement('position');
    }

    public function addCardToTop(): void
    {
        $this->shiftCardsDown();
    }

    public function removeCardFromSequence(int $position): void
    {
        $this->shiftCardsUp($position);
    }

    public function move(int $newPosition): void
    {
        if ($this->position === $newPosition) {
            return;
        }

        DB::transaction(function () use ($newPosition) {
            $oldPosition = $this->position;

            match (true) {
                $newPosition < $oldPosition => $this->board->columns()
                    ->where('position', '>=', $newPosition)
                    ->where('position', '<', $oldPosition)
                    ->increment('position'),

                default => $this->board->columns()
                    ->where('position', '>', $oldPosition)
                    ->where('position', '<=', $newPosition)
                    ->decrement('position'),
            };

            $this->update(['position' => $newPosition]);
        });
    }
}
