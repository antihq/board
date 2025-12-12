<?php

use App\Models\Column;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Async;
use Livewire\Attributes\Renderless;
use Livewire\Component;

new class extends Component
{
    public Column $column;

    public bool $show = false;

    public string $title = '';

    public function add()
    {
        DB::transaction(function () {
            $this->column->shiftCardsDown();

            $this->column->cards()->create([
                'title' => $this->pull('title'),
                'position' => 1,
                'user_id' => Auth::id(),
            ]);
        });
    }

    #[Renderless, Async]
    public function moveCard($item, $position)
    {
        $card = $this->column->board->cards()->findOrFail($item);

        $card->moveInto($this->column, $position);
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
    <flux:kanban.column.header :heading="$column->name" :count="$this->column->cards->count()" />
    <flux:kanban.column.cards wire:sort="moveCard" wire:sort:group="columns">
        @foreach ($this->column->cards as $card)
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
