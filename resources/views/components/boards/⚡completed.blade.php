<?php

use App\Models\Board;
use App\Models\Card;
use Livewire\Attributes\Async;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Renderless;
use Livewire\Component;

new class extends Component
{
    public Board $board;

    #[Renderless, Async]
    public function moveCard($item, $position)
    {
        $card = $this->board->cards()->findOrFail($item);
        $card->moveToCompleted($position);
    }

    #[Computed]
    public function completedCards()
    {
        return $this->board->cards()
            ->completed()
            ->orderBy('position', 'asc')
            ->get();
    }
};
?>

@placeholder
    <flux:skeleton.group animate="shimmer">
        <flux:skeleton.line class="mb-2 w-1/4" />
        <flux:skeleton.line />
        <flux:skeleton.line />
        <flux:skeleton.line class="w-3/4" />
    </flux:skeleton.group>
@endplaceholder

<flux:kanban.column :$attributes>
    <flux:kanban.column.header heading="Done" count="{{ $this->completedCards->count() }}" />
    <flux:kanban.column.cards wire:sort="moveCard" wire:sort:group="columns">
        @foreach ($this->completedCards as $card)
            <livewire:boards.card :$card wire:key="{{ $card->id }}" wire:sort:item="{{ $card->id }}" />
        @endforeach
    </flux:kanban.column.cards>
</flux:kanban.column>
