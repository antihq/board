<?php

use App\Models\Card;
use Livewire\Component;

new class extends Component
{
    public Card $card;

    public string $title;

    public ?string $description;

    public int $column;

    public bool $show = false;

    public function mount()
    {
        $this->title = $this->card->title;
        $this->description = $this->card->description;
        $this->column = $this->card->column_id;
    }

    public function save()
    {
        $this->card->update([
            'title' => $this->title,
            'description' => $this->description,
        ]);

        $this->show = false;
    }

    public function move()
    {
        $column = $this->card->board->columns()->findOrFail($this->column);

        $this->card->update([
            'column_id' => $column->id,
        ]);
    }
};
?>

<div {{ $attributes }}>
    <flux:modal class="h-full w-full max-w-216 pt-1.5 pr-1.5 pb-1.5" @close="$refresh">
        <x-slot name="trigger">
            <flux:kanban.card as="button" :heading="$card->title" />
        </x-slot>

        @island(lazy: true)
            @placeholder
                <flux:skeleton.group animate="shimmer">
                    <flux:skeleton.line class="mb-2 w-1/4" />
                    <flux:skeleton.line />
                    <flux:skeleton.line />
                    <flux:skeleton.line class="w-3/4" />
                </flux:skeleton.group>
            @endplaceholder

            <div class="flex h-full w-full gap-4">
                <div class="flex-1 py-4.5">
                    <div class="space-y-4" wire:show="!show">
                        <header class="flex items-center justify-between">
                            <flux:button
                                wire:click="$js.reveal"
                                variant="ghost"
                                inset="left right top bottom"
                                align="start"
                                class="w-full text-xl"
                            >
                                {{ $card->title }}
                            </flux:button>
                        </header>
                        @if ($card->description)
                            <div class="prose prose-sm prose-zinc max-w-none">
                                {!! $card->description !!}
                            </div>
                        @endif
                    </div>
                    <form wire:submit="save" wire:show="show" class="space-y-4" x-cloak>
                        <div>
                            <flux:heading>
                                <input
                                    wire:model="title"
                                    wire:ref="input"
                                    placeholder="Enter card title..."
                                    class="w-full text-xl outline-none"
                                />
                            </flux:heading>
                            <flux:error name="title" />
                        </div>

                        <flux:field>
                            <flux:editor
                                wire:model="description"
                                placeholder="Enter card description..."
                                toolbar="bold italic | bullet | link"
                                class="**:data-[slot=content]:min-h-[150px]!"
                            />
                            <flux:error name="description" />
                        </flux:field>

                        <div class="flex items-center gap-2">
                            <flux:button wire:click="$js.conceal" variant="subtle" size="sm">Cancel</flux:button>
                            <flux:button type="submit" variant="filled" size="sm">Save</flux:button>
                        </div>
                    </form>
                </div>

                <div class="w-70 rounded-lg bg-zinc-100 p-4.5">
                    <flux:radio.group
                        wire:model="column"
                        variant="buttons"
                        class="w-full *:flex-1"
                        label="Move to column"
                        wire:change="move"
                    >
                        @foreach ($this->card->board->columns as $column)
                            <flux:radio :value="$column->id" size="sm">
                                {{ $column->name }}
                            </flux:radio>
                        @endforeach
                    </flux:radio.group>
                </div>
            </div>
        @endisland
    </flux:modal>
</div>

<script>
    this.$js.reveal = () => {
        this.show = true;

        setTimeout(() => {
            this.$refs.input.focus();
        });
    };

    this.$js.conceal = () => {
        this.show = false;
    };

    this.$el.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            if (this.show) {
                this.show = false;
            }
        }
    });
</script>
