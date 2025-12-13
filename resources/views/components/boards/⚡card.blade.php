<?php

use App\Models\Card;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public Card $card;

    public string $title;

    public ?string $description;

    public int $column;

    public bool $show = false;

    public array $assignedUserIds = [];

    public bool $showAddTag = false;

    public array $tags = [];

    public string $tagTitle = '';

    #[Computed]
    public function displayAssignees()
    {
        return $this->card->assignees->take(3);
    }

    #[Computed]
    public function remainingAssigneesCount()
    {
        $total = $this->card->assignees->count();

        return $total > 3 ? $total - 3 : 0;
    }

    public function mount()
    {
        $this->title = $this->card->title;
        $this->description = $this->card->description;
        $this->column = $this->card->column_id;
        $this->tags = $this->card->tags->pluck('id')->toArray();
        $this->assignedUserIds = $this->card->assignees->pluck('id')->toArray();
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
    }

    public function syncTags()
    {
        $validTagIds = $this->card->board->team->tags()
            ->whereIn('id', $this->tags)
            ->pluck('id')
            ->toArray();

        $this->card->tags()->sync($validTagIds);
    }

    public function syncAssignedUsers()
    {
        $validUserIds = $this->card->board->team->allMembers()
            ->whereIn('id', $this->assignedUserIds)
            ->pluck('id')
            ->toArray();

        $this->card->assignees()->sync($validUserIds);
    }
};
?>

<div {{ $attributes }}>
    <flux:modal class="h-full w-full max-w-216 pt-1.5 pr-1.5 pb-1.5" @close="$refresh">
        <x-slot name="trigger">
            <flux:kanban.card as="button" :heading="$card->title" class="h-full">
                @unless($card->tags->isEmpty())
                    <x-slot name="header">
                        <div class="flex gap-2 items-center">
                            <flux:icon name="tag" variant="micro" class="text-zinc-400" />

                            @foreach ($card->tags as $tag)
                                <flux:badge size="sm">{{ $tag->name }}</flux:badge>
                            @endforeach
                        </div>
                    </x-slot>
                @endunless
                @unless($this->displayAssignees->isEmpty())
                    <x-slot name="footer">
                        <flux:icon name="bars-3-bottom-left" variant="micro" class="text-zinc-400" />

                        <flux:avatar.group>
                            @foreach ($this->displayAssignees as $assignee)
                                <flux:avatar circle size="xs" :name="$assignee->name" :src="$assignee->avatar_url ?? null" color="auto" :color:seed="$assignee->id" />
                            @endforeach

                            @if ($this->remainingAssigneesCount > 0)
                                <flux:avatar circle size="xs">{{ $this->remainingAssigneesCount }}+</flux:avatar>
                            @endif
                        </flux:avatar.group>
                    </x-slot>
                @endunless
            </flux:kanban.card>
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

                    <flux:pillbox
                        wire:model="assignedUserIds"
                        label="Assignees"
                        multiple
                        searchable
                        placeholder="Choose team members..."
                        size="sm"
                        wire:change="syncAssignedUsers"
                    >
                        @foreach ($this->card->board->team->allMembers() as $member)
                            <flux:pillbox.option :value="$member->id">
                                <div class="flex items-center gap-2">
                                    <flux:avatar :src="$member->avatar_url ?? null" size="xs" circle />
                                    {{ $member->name }}
                                </div>
                            </flux:pillbox.option>
                        @endforeach
                    </flux:pillbox>

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
                            @foreach ($this->card->board->team->tags as $tag)
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
