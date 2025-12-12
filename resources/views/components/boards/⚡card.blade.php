<?php

use App\Models\Card;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public Card $card;

    public string $title;

    public ?string $description;

    public int $column;

    public bool $show = false;

    public bool $showAddTag = false;

    public array $tags = [];

    public string $tagTitle = '';

    public Collection $teamTags;

    public function mount()
    {
        $this->title = $this->card->title;
        $this->description = $this->card->description;
        $this->column = $this->card->column_id;
        $this->tags = $this->card->tags->pluck('id')->toArray();
        $this->teamTags = $this->card->board->team->tags()->orderBy('name')->get();
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

    public function addTag()
    {
        $this->validate([
            'tagTitle' => 'required|string|max:255|unique:tags,name,NULL,id,team_id,'.$this->card->board->team_id,
        ]);

        $tag = $this->card->board->team->tags()->create([
            'name' => $this->pull('tagTitle'),
            'user_id' => Auth::id(),
        ]);

        $this->tags[] = $tag->id;
        $this->card->tags()->attach($tag->id);

        $this->tagTitle = '';
        $this->showAddTag = false;
        $this->teamTags = $this->card->board->team->tags()->orderBy('name')->get();
    }

    public function syncTags()
    {
        $validTagIds = $this->card->board->team->tags()
            ->whereIn('id', $this->tags)
            ->pluck('id')
            ->toArray();

        $this->card->tags()->sync($validTagIds);
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

                <div class="w-70 space-y-6 rounded-lg bg-zinc-100 p-4.5">
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

                    <div class="space-y-2">
                        <flux:pillbox
                            wire:model="tags"
                            label="Tags"
                            multiple
                            searchable
                            placeholder="Choose tags..."
                            size="sm"
                            wire:change="syncTags"
                        >
                            @foreach ($teamTags as $tag)
                                <flux:pillbox.option :value="$tag->id">
                                    {{ $tag->name }}
                                </flux:pillbox.option>
                            @endforeach
                        </flux:pillbox>

                        <form wire:submit="addTag" wire:show="showAddTag">
                            <flux:input.group>
                                <flux:input
                                    wire:model="tagTitle"
                                    wire:ref="newTagInput"
                                    placeholder="New tag"
                                    size="sm"
                                />
                                <flux:button type="submit" size="sm">Add</flux:button>
                            </flux:input.group>
                        </form>

                        <flux:button
                            wire:click="$js.revealAddTag"
                            wire:show="!showAddTag"
                            variant="subtle"
                            icon="plus"
                            size="sm"
                            align="start"
                        >
                            New tag
                        </flux:button>
                    </div>
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

    this.$js.revealAddTag = () => {
        this.showAddTag = true;

        setTimeout(() => {
            this.$refs.newTagInput.focus();
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

            if (this.showAddTag) {
                this.showAddTag = false;
            }
        }
    });
</script>
