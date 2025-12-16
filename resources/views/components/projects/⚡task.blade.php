<?php

use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public Task $task;

    public string $title;

    public ?string $description;

    public ?int $section;

    public ?string $location = null;

    public bool $show = false;

    public array $assignedUserIds = [];

    public bool $showAddTag = false;

    public array $tags = [];

    public string $tagTitle = '';

    public string $commentBody = '';

    #[Computed]
    public function displayAssignees()
    {
        return $this->task->assignees->take(3);
    }

    #[Computed]
    public function remainingAssigneesCount()
    {
        $total = $this->task->assignees->count();

        return $total > 3 ? $total - 3 : 0;
    }

    #[Computed]
    public function taskComments()
    {
        return $this->task->comments()->with('user')->oldest()->get();
    }

    public function mount()
    {
        $this->title = $this->task->title;
        $this->description = $this->task->description;
        $this->section = $this->task->section_id;
        $this->tags = $this->task->tags->pluck('id')->toArray();
        $this->assignedUserIds = $this->task->assignees->pluck('id')->toArray();
    }

    public function save()
    {
        $this->task->update([
            'title' => $this->title,
            'description' => $this->description,
        ]);

        $this->show = false;
    }

    public function addTag()
    {
        $this->validate([
            'tagTitle' => 'required|string|max:255|unique:tags,name,NULL,id,team_id,'.$this->task->project->team_id,
        ]);

        $tag = $this->task->project->team->tags()->create([
            'name' => $this->pull('tagTitle'),
            'user_id' => Auth::id(),
        ]);

        $this->tags[] = $tag->id;
        $this->task->tags()->attach($tag->id);

        $this->tagTitle = '';
        $this->showAddTag = false;
    }

    public function syncTags()
    {
        $validTagIds = $this->task->project->team->tags()
            ->whereIn('id', $this->tags)
            ->pluck('id')
            ->toArray();

        $this->task->tags()->sync($validTagIds);
    }

    public function syncAssignedUsers()
    {
        $validUserIds = $this->task->project->team->allMembers()
            ->whereIn('id', $this->assignedUserIds)
            ->pluck('id')
            ->toArray();

        $this->task->assignees()->sync($validUserIds);
    }

    public function addComment()
    {
        $this->validate([
            'commentBody' => 'required|string|max:2000',
        ]);

        $this->task->comments()->create([
            'comment_body' => $this->pull('commentBody'),
            'user_id' => Auth::id(),
        ]);
    }
};
?>

<div {{ $attributes }}>
    <flux:modal class="h-full w-full max-w-216 pt-1.5 pr-1.5 pb-1.5" @close="$refresh">
        <x-slot name="trigger">
            <flux:kanban.card as="button" :heading="$task->title" class="h-full">
                @unless ($task->tags->isEmpty())
                    <x-slot name="header">
                        <div class="flex items-center gap-2">
                            <flux:icon name="tag" variant="micro" class="text-zinc-400" />

                            @foreach ($task->tags as $tag)
                                <flux:badge size="sm">{{ $tag->name }}</flux:badge>
                            @endforeach
                        </div>
                    </x-slot>
                @endunless

                @unless ($this->displayAssignees->isEmpty())
                    <x-slot name="footer">
                        <flux:icon name="bars-3-bottom-left" variant="micro" class="text-zinc-400" />

                        <flux:avatar.group>
                            @foreach ($this->displayAssignees as $assignee)
                                <flux:avatar
                                    circle
                                    size="xs"
                                    :name="$assignee->name"
                                    :src="$assignee->avatar_url ?? null"
                                    color="auto"
                                    :color:seed="$assignee->id"
                                />
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
                                {{ $task->title }}
                            </flux:button>
                        </header>
                        @if ($task->description)
                            <div class="prose prose-sm prose-zinc max-w-none">
                                {!! $task->description !!}
                            </div>
                        @endif
                    </div>
                    <form wire:submit="save" wire:show="show" class="space-y-4" x-cloak>
                        <div>
                            <flux:heading>
                                <input
                                    wire:model="title"
                                    wire:ref="input"
                                    placeholder="Enter task title..."
                                    class="w-full text-xl outline-none"
                                />
                            </flux:heading>
                            <flux:error name="title" />
                        </div>

                        <flux:field>
                            <flux:editor
                                wire:model="description"
                                placeholder="Enter task description..."
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

                    <flux:spacer class="my-8" />

                    @if ($this->taskComments->isNotEmpty())
                        <div class="mb-6 space-y-6">
                            @foreach ($this->taskComments as $comment)
                                <div class="flex gap-3">
                                    <flux:avatar
                                        :src="$comment->user->avatar_url ?? null"
                                        size="sm"
                                        circle
                                        :name="$comment->user->name"
                                    />
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <flux:text variant="strong">{{ $comment->user->name }}</flux:text>
                                            <flux:text class="text-xs">
                                                {{ $comment->created_at->diffForHumans() }}
                                            </flux:text>
                                        </div>
                                        <div class="prose prose-sm max-w-none">
                                            {!! $comment->comment_body !!}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <form wire:submit="addComment">
                        <flux:composer
                            wire:model="commentBody"
                            label="Comment"
                            label:sr-only
                            placeholder="Add a comment..."
                        >
                            <x-slot name="input">
                                <flux:editor variant="borderless" toolbar="bold italic bullet ordered | link | align" />
                            </x-slot>

                            <x-slot name="actionsLeading">
                                <flux:button size="sm" variant="subtle" icon="paper-clip" disabled />
                            </x-slot>

                            <x-slot name="actionsTrailing">
                                <flux:button size="sm" icon="check-circle">
                                    Close task
                                </flux:button>
                                <flux:button type="submit" size="sm" variant="primary" color="zinc">
                                    Comment
                                </flux:button>
                            </x-slot>
                        </flux:composer>
                    </form>
                </div>

                <div class="w-70 space-y-6 rounded-lg bg-zinc-100 p-4.5">
                    <flux:radio.group
                        wire:model="location"
                        variant="buttons"
                        class="w-full *:flex-1"
                        label="Move to"
                        wire:change="move"
                    >
                        <flux:radio value="postponed" size="sm">Not now</flux:radio>

                        <flux:radio value="opened" size="sm">Maybe?</flux:radio>

                        @if ($this->task->project->sections->isNotEmpty())
                            @foreach ($this->task->project->sections as $section)
                                <flux:radio value="section:{{ $section->id }}" size="sm">
                                    {{ $section->name }}
                                </flux:radio>
                            @endforeach
                        @endif

                        <flux:radio value="completed" size="sm">Done</flux:radio>
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
                        @foreach ($this->task->project->team->allMembers() as $member)
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
                            @foreach ($this->task->project->team->tags as $tag)
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
