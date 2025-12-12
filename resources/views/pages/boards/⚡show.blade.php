<?php

use App\Models\Board;
use Livewire\Attributes\Async;
use Livewire\Attributes\Renderless;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Board')] class extends Component
{
    public Board $board;

    public bool $show = false;

    public string $name = '';

    public function mount()
    {
        $this->authorize('view', $this->board);
    }

    public function add()
    {
        $this->board->columns()->create([
            'name' => $this->pull('name'),
            'position' => 0,
        ]);
    }

    #[Renderless, Async]
    public function moveColumn($item, $position)
    {
        $column = $this->board->columns()->findOrFail($item);

        $column->move($position);
    }
};
?>

<div class="h-full">
    <flux:heading>{{ $board->name }}</flux:heading>

    <flux:spacer class="my-4" />

    <div class="relative h-full">
        <div class="absolute w-48 h-full inset-y-0 right-0 bg-gradient-to-l from-white to-transparent dark:from-zinc-900 dark:to-transparent"></div>
        <div class="overflow-x-auto h-full w-full">
            <flux:kanban wire:sort="moveColumn">
                @foreach ($this->board->columns as $column)
                    <livewire:boards.column :column="$column" wire:key="{{ $column->id }}" wire:sort:item="{{ $column->id }}" />
                @endforeach

                <flux:kanban.column>
                    <flux:kanban.column.footer class="pt-2">
                        <form wire:submit="add" wire:show="show" wire:cloak>
                            <flux:kanban.card>
                                <div class="flex items-center gap-1">
                                    <flux:heading class="flex-1">
                                        <input
                                            wire:model="name"
                                            wire:ref="input"
                                            placeholder="New column..."
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
                            New column
                        </flux:button>
                    </flux:kanban.column.footer>
                </flux:kanban.column>
                <div class="w-48 h-full shrink-0">&nbsp;</div>
            </flux:kanban>
        </div>
    </div>
</div>

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
