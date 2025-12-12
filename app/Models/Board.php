<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Board extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function columns()
    {
        return $this->hasMany(Column::class)->orderBy('position');
    }

    public function cards()
    {
        return $this->hasManyThrough(Card::class, Column::class)->orderBy('position');
    }

    public function maybeColumn()
    {
        return $this->columns()->where('name', 'Maybe?')->first();
    }

    public function createDefaultColumns()
    {
        return $this->columns()->createMany([
            ['name' => 'Not Now', 'position' => 1],
            ['name' => 'Maybe?', 'position' => 2],
            ['name' => 'Done', 'position' => 3],
        ]);
    }
}
