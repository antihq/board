<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChecklistItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function delete()
    {
        parent::delete();

        $this->task->touch();
    }

    protected function casts(): array
    {
        return [
            'completed' => 'boolean',
        ];
    }

    #[Scope]
    protected function completed(Builder $query): void
    {
        $query->where('completed', true);
    }
}
