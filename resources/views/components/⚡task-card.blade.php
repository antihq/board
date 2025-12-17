<?php

use App\Models\Task;
use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\Computed;

new class extends Component
{
    public Task $task;

    public bool $showModal = false;

    public string $description = '';

    public bool $isEditingDescription = false;

    public bool $isEditingTitle = false;

    public string $title = '';

    public string $newComment = '';

    public function openModal()
    {
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->isEditingDescription = false;
        $this->isEditingTitle = false;
        $this->newComment = '';
    }

    public function editDescription()
    {
        $this->description = $this->task->description ?? '';
        $this->isEditingDescription = true;
    }

    public function saveDescription()
    {
        $this->validate([
            'description' => 'nullable|string|max:5000',
        ]);

        $this->task->update([
            'description' => $this->description,
        ]);

        $this->isEditingDescription = false;
    }

    public function editTitle()
    {
        $this->title = $this->task->title;
        $this->isEditingTitle = true;
    }

    public function saveTitle()
    {
        $this->validate([
            'title' => 'required|string|max:255',
        ]);

        $this->task->update([
            'title' => $this->title,
        ]);

        $this->isEditingTitle = false;
    }

    public function cancelEdit()
    {
        $this->isEditingDescription = false;
        $this->description = '';
        $this->isEditingTitle = false;
        $this->title = '';
    }

    public function addComment()
    {
        $this->validate([
            'newComment' => 'required|string|max:5000',
        ]);

        $this->task->comments()->create([
            'user_id' => Auth::id(),
            'content' => $this->pull('newComment'),
        ]);
    }

    #[Computed]
    public function comments()
    {
        return $this->task->comments()->with('user')->get();
    }
};
?>

<flux:modal class="w-full max-w-[95vw] md:w-[600px]">
    <x-slot name="trigger">
        <flux:kanban.card
            as="button"
            heading="{{ $this->isEditingTitle ? $this->title : $task->title }}"
            wire:sort:item="{{ $task->id }}"
        />
    </x-slot>

    <div class="space-y-6">
        <div>
            @if ($this->isEditingTitle)
                <form wire:submit="saveTitle">
                    <flux:composer
                        wire:model="title"
                        rows="1"
                        label="Task Title"
                        label:sr-only
                        placeholder="Enter task title..."
                        submit="enter"
                        inline
                    >
                        <x-slot name="actionsTrailing">
                            <flux:button type="button" size="sm" wire:click="cancelEdit">Cancel</flux:button>
                            <flux:button type="submit" size="sm" variant="primary" color="green">Save</flux:button>
                        </x-slot>
                    </flux:composer>
                </form>
            @else
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <flux:heading size="lg">{{ $task->title }}</flux:heading>
                        <flux:button size="xs" wire:click="editTitle">Edit</flux:button>
                    </div>
                    @if ($task->completed_at)
                        <flux:badge color="purple" size="lg" icon="check-circle">Closed</flux:badge>
                    @else
                        <flux:badge color="green" size="lg" icon="clock">Open</flux:badge>
                    @endif
                </div>
            @endif
        </div>

        @if ($this->isEditingDescription)
            <form wire:submit="saveDescription">
                <div>
                    <flux:composer
                        wire:model="description"
                        rows="6"
                        max-rows="12"
                        label="Task Description"
                        label:sr-only
                        placeholder="Add a detailed description..."
                    >
                        <x-slot name="input">
                            <flux:editor
                                variant="borderless"
                                toolbar="heading | bold italic | bullet ordered | link"
                                placeholder="Add a detailed description..."
                            />
                        </x-slot>
                        <x-slot name="actionsLeading"></x-slot>
                        <x-slot name="actionsTrailing">
                            <flux:button type="button" size="sm" wire:click="cancelEdit">Cancel</flux:button>
                            <flux:button type="submit" size="sm" variant="primary" color="green">Save</flux:button>
                        </x-slot>
                    </flux:composer>
                </div>
            </form>
        @else
            <div class="space-y-4">
                @if ($task->description)
                    <div class="prose prose-sm prose-zinc dark:prose-invert max-w-none">
                        {!! $task->description !!}
                    </div>
                    <flux:button size="xs" wire:click="editDescription">Edit description</flux:button>
                @else
                    <flux:button size="xs" wire:click="editDescription">Add description</flux:button>
                @endif
            </div>
        @endif

        <flux:separator variant="subtle" />

        <!-- Comments Section -->
        <div class="space-y-4">
            @unless ($this->comments->isEmpty())
                <flux:heading class="text-xs!">Comments</flux:heading>

                <!-- Comments List -->
                <div class="space-y-3">
                    @foreach ($this->comments as $comment)
                        <div class="flex gap-3">
                            <flux:avatar
                                circle
                                size="sm"
                                name="{{ $comment->user->name }}"
                                color="auto"
                                color:seed="{{ $comment->user->id }}"
                                tooltip="{{ $comment->user->name }}"
                                src="https://unavatar.io/gravatar/{{ auth()->user()->email }}"
                            />
                            <div class="flex-1 space-y-1">
                                <div class="flex items-center gap-2">
                                    <flux:heading>{{ $comment->user->name }}</flux:heading>
                                    <flux:text class="text-xs">{{ $comment->created_at->diffForHumans() }}</flux:text>
                                </div>
                                <div class="prose prose-sm prose-zinc dark:prose-invert max-w-none">
                                    {!! $comment->content !!}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endunless

            <!-- Add Comment Form -->
            <form wire:submit="addComment" class="space-y-3">
                <flux:composer
                    wire:model="newComment"
                    rows="3"
                    max-rows="8"
                    label="Add a comment"
                    label:sr-only
                    placeholder="Write a comment..."
                >
                    <x-slot name="input">
                        <flux:editor
                            variant="borderless"
                            toolbar="bold italic | link"
                            placeholder="Write a comment..."
                        />
                    </x-slot>
                    <x-slot name="actionsLeading"></x-slot>
                    <x-slot name="actionsTrailing">
                        <flux:button type="submit" size="sm" variant="primary" color="green">Comment</flux:button>
                    </x-slot>
                </flux:composer>
            </form>
        </div>
    </div>
</flux:modal>
