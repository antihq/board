<?php

use App\Models\Board;
use App\Models\Card;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Async;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Renderless;
use Livewire\Component;

new class extends Component
{
    public Board $board;

    public bool $show = false;

    public string $title = '';

    public function add()
    {
        Card::addToOpened($this->board, $this->title, Auth::id());
        $this->show = false;
    }

    #[Renderless, Async]
    public function moveCard($item, $position)
    {
        $card = $this->board->cards()->findOrFail($item);
        $card->moveToOpened($position);
    }

    #[Computed]
    public function openedCards()
    {
        return $this->board->cards()->opened()->orderBy('position', 'asc')->get();
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
    <flux:kanban.column.header heading="Maybe?" count="{{ $this->openedCards->count() }}" />
    <flux:kanban.column.cards wire:sort="moveCard" wire:sort:group="columns">
        @foreach ($this->openedCards as $card)
            <livewire:boards.card :$card wire:key="{{ $card->id }}" wire:sort:item="{{ $card->id }}" />
        @endforeach
    </flux:kanban.column.cards>
    <flux:kanban.column.footer>
        <form wire:submit="add" wire:show="show" wire:cloak>
            <flux:kanban.card>
                <div class="flex items-center gap-1">
                    <flux:heading class="flex-1">
                        <input
                            wire:model="title"
                            wire:ref="input"
                            placeholder="New card..."
                            class="w-full outline-none"
                        />
                    </flux:heading>

                    <flux:button type="submit" variant="filled" size="sm" inset="top bottom" class="-me-1.5">
                        Add
                    </flux:button>
                </div>
            </flux:kanban.card>
        </form>
        <flux:button wire:click="$js.reveal" wire:show="!show" variant="subtle" icon="plus" size="sm" align="start">
            New card
        </flux:button>
    </flux:kanban.column.footer>
</flux:kanban.column>

<script>
    this.$js.reveal = () => {
        this.show = true;

        setTimeout(() => {
            this.$refs.input.focus();
        });
    };

    this.$el.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            if (this.show) {
                this.show = false;
            }
        }
    });
</script>
