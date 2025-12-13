<?php

use App\Models\Card;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Cards')] class extends Component
{
    public Team $team;

    public function mount()
    {
        $this->team = Auth::user()->currentTeam;
    }

    #[Computed]
    public function cards()
    {
        return Card::whereHas('column.board.team', function ($query) {
            $query->where('id', $this->team->id);
        })->orderBy('updated_at', 'desc')->get();
    }
};
?>

<div class="h-full">
    <flux:heading>Cards</flux:heading>

    <flux:spacer class="my-4" />

    <div class="grid lg:grid-cols-3 gap-2">
        @foreach ($this->cards as $card)
            <livewire:boards.card :card="$card" :key="'card-' . $card->id" class="rounded-lg shadow-xs" />
        @endforeach
    </div>
</div>
