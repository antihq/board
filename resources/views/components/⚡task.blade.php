<?php

use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public Task $task;

    public string $description = '';

    public bool $isEditingDescription = false;

    public bool $isEditingTitle = false;

    public string $title = '';

    public string $newComment = '';

    public bool $isAddingChecklistItem = false;

    public string $newChecklistItem = '';

    public array $completedChecklistItems = [];

    public array $selectedTags = [];

    public string $tagSearch = '';

    public bool $isManagingTags = false;

    public bool $isManagingSection = false;

    public ?int $selectedSection = null;

    public bool $isManagingAssignees = false;

    public array $selectedAssignees = [];

    public array $images = [];

    public array $commentImages = [];

    public function mount()
    {
        $this->title = $this->task->title;

        $this->completedChecklistItems = $this->task
            ->completedChecklistItems()
            ->pluck('id')
            ->toArray();

        $this->selectedTags = $this->task
            ->tags()
            ->pluck('tags.id')
            ->toArray();

        $this->selectedSection = $this->task->section_id;

        $this->selectedAssignees = $this->task
            ->assignees()
            ->pluck('users.id')
            ->toArray();
    }

    public function close()
    {
        $this->task->close(Auth::user());

        $this->dispatch('task.moved');
    }

    public function complete()
    {
        $this->task->complete(Auth::user());

        $this->dispatch('task.moved');
    }

    public function reopen()
    {
        $this->task->reopen(Auth::user());

        $this->dispatch('task.moved');
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

    public function cancelEditTitle()
    {
        $this->title = $this->task->title;

        $this->isEditingTitle = false;
    }

    public function editDescription()
    {
        $this->description = $this->task->description ?? '';

        $this->isEditingDescription = true;
    }

    public function saveDescription()
    {
        $this->authorize('update', $this->task);

        $this->validate([
            'description' => 'nullable|string|max:5000',
            'images.*' => 'image|max:10240',
            'images' => 'max:' . $this->maxAllowedUploads(),
        ]);

        DB::transaction(function () {
            $this->task->lockForUpdate();

            $this->task->update([
                'description' => $this->description,
            ]);

            foreach ($this->images as $image) {
                $this->task->attachImage($image);
            }
        });

        $this->reset('images');

        $this->isEditingDescription = false;
    }

    public function removeImage($index)
    {
        $image = $this->images[$index];

        $image->delete();

        unset($this->images[$index]);

        $this->images = array_values($this->images);
    }

    public function togglePriority()
    {
        if ($this->task->isPrioritized()) {
            $this->task->unprioritize();

            return;
        }

        $this->task->prioritize(Auth::user());
    }

    public function updatedSelectedSection()
    {
        if ($this->selectedSection === null && $this->task->section_id === null) {
            return;
        }

        if ($this->selectedSection === null) {
            $this->task->moveToPending(Auth::user());
        } else {
            $section = $this->task->project->sections()->findOrFail($this->selectedSection);
            $this->task->moveToSection($section, Auth::user());
        }

        $this->dispatch('task.moved');
    }

    public function updatedSelectedTags()
    {
        $this->task->syncTags($this->selectedTags);
    }

    public function addTag()
    {
        $this->validate([
            'tagSearch' => 'required|string|max:255',
        ]);

        $tag = $this->task->addTag($this->pull('tagSearch'));

        $this->selectedTags[] = $tag->id;
    }

    public function updatedSelectedAssignees()
    {
        $this->authorize('manageAssignees', $this->task);

        $this->task->syncAssignees($this->selectedAssignees);
    }

    public function toggleSubscribe()
    {
        $this->authorize('update', $this->task);

        if ($this->task->isSubscribed(Auth::user())) {
            $this->task->unsubscribe(Auth::user());

            return;
        }

        $this->task->subscribe(Auth::user());
    }

    public function toggleSaved()
    {
        $this->authorize('update', $this->task);

        if ($this->task->isSaved(Auth::user())) {
            $this->task->removeFromSaved(Auth::user());

            return;
        }

        $this->task->addToSaved(Auth::user());
    }

    public function addComment()
    {
        $this->validate([
            'newComment' => 'required|string|max:5000',
            'commentImages.*' => 'image|max:10240',
            'commentImages' => 'max:' . $this->maxAllowedCommentUploads(),
        ]);

        $comment = $this->task->addComment($this->pull('newComment'));

        foreach ($this->commentImages as $image) {
            $comment->attachImage($image);
        }

        $this->reset('commentImages');
    }

    public function deleteComment($commentId)
    {
        $comment = $this->task->comments()->findOrFail($commentId);

        $this->authorize('delete', $comment);

        $comment->delete();
    }

    public function removeCommentImage($index)
    {
        $image = $this->commentImages[$index];

        $image->delete();

        unset($this->commentImages[$index]);

        $this->commentImages = array_values($this->commentImages);
    }

    public function addChecklist()
    {
        $this->validate([
            'newChecklistItem' => 'required|string|max:500',
        ]);

        $this->task->addChecklistItem($this->pull('newChecklistItem'));
    }

    public function updatedCompletedChecklistItems()
    {
        $this->task->syncChecklist($this->completedChecklistItems);
    }

    public function deleteChecklistItem($id)
    {
        $item = $this->task->checklistItems()->findOrFail($id);

        $this->authorize('update', $this->task);

        $item->delete();

        $key = array_search($id, $this->completedChecklistItems);

        if ($key !== false) {
            unset($this->completedChecklistItems[$key]);
            $this->completedChecklistItems = array_values($this->completedChecklistItems);
        }
    }

    #[Computed]
    public function checklistItems()
    {
        return $this->task
            ->checklistItems()
            ->oldest()
            ->get();
    }

    #[Computed]
    public function comments()
    {
        return $this->task
            ->comments()
            ->with('user', 'editor', 'images')
            ->get();
    }

    #[Computed]
    public function teamTags()
    {
        return $this->task->team
            ->tags()
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function projectSections()
    {
        return $this->task->project
            ->sections()
            ->orderBy('order')
            ->get();
    }

    #[Computed]
    public function teamMembers()
    {
        return $this->task->team->allUsers();
    }

    #[Computed]
    public function taskImages()
    {
        return $this->task->images;
    }

    private function maxAllowedUploads(): int
    {
        return max(0, 4 - $this->task->images()->count());
    }

    private function maxAllowedCommentUploads(): int
    {
        return 4;
    }
};
?>

@placeholder
    <!-- skeletons -->
    <flux:skeleton.group animate="shimmer" class="space-y-6">
        <!-- Task title and badges section -->
        <div>
            <div class="mb-3 flex items-center gap-2">
                <flux:skeleton.line class="h-6 w-full" />
            </div>
            <div class="flex flex-wrap gap-2">
                <flux:skeleton class="h-6 w-16 rounded-full" />
                <flux:skeleton class="h-6 w-16 rounded-full" />
            </div>
        </div>

        <!-- Description section -->
        <flux:skeleton class="h-20 w-full rounded" />

        <!-- Grid: Section, Priority, Tags -->
        <div class="grid grid-cols-2 gap-4">
            <!-- Section -->
            <div class="space-y-2">
                <flux:skeleton.line class="h-4" />
                <flux:skeleton.line class="h-6 w-20" />
            </div>

            <!-- Priority -->
            <div class="space-y-2">
                <flux:skeleton.line class="h-4" />
                <flux:skeleton.line class="h-6 w-20" />
            </div>
        </div>

        <!-- Checklist section -->
        <div class="space-y-3">
            <flux:skeleton.line class="h-4 w-20" />
            <div class="space-y-2">
                <div class="flex items-center gap-2">
                    <flux:skeleton class="h-4 w-4 rounded" />
                    <flux:skeleton.line class="h-4 flex-1" />
                </div>
                <div class="flex items-center gap-2">
                    <flux:skeleton class="h-4 w-4 rounded" />
                    <flux:skeleton.line class="h-4 w-3/4 flex-1" />
                </div>
            </div>
        </div>

        <!-- Comments section -->
        <div class="space-y-4">
            <flux:skeleton.line class="h-6 w-20" />

            <!-- Comment items -->
            <div class="space-y-3">
                <div class="flex gap-3">
                    <flux:skeleton class="h-8 w-8 rounded-full" />
                    <div class="flex-1 space-y-2">
                        <flux:skeleton.line class="h-4 w-20" />
                        <flux:skeleton class="h-12 w-full rounded" />
                    </div>
                </div>
                <div class="flex gap-3">
                    <flux:skeleton class="h-8 w-8 rounded-full" />
                    <div class="flex-1 space-y-2">
                        <flux:skeleton.line class="h-4 w-20" />
                        <flux:skeleton class="h-12 w-full rounded" />
                    </div>
                </div>
            </div>
        </div>
    </flux:skeleton.group>
@endplaceholder

<div>
    <div class="space-y-4">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item wire:navigate :href="'/' . $task->team->handle . '/' . $task->project->handle">
                {{ $task->project->name }}
            </flux:breadcrumbs.item>
            <flux:breadcrumbs.item>#{{ $task->number }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <div class="flex flex-col gap-6 md:flex-row">
            <!-- Main Content -->
            <div class="min-w-0 flex-1 space-y-6">
                <div>
                    <form wire:submit="saveTitle" wire:show="isEditingTitle" wire:cloak>
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
                                <flux:button type="button" size="sm" wire:click="cancelEditTitle">Cancel</flux:button>
                                <flux:button type="submit" size="sm" variant="primary">Save</flux:button>
                            </x-slot>
                        </flux:composer>
                    </form>
                    <div class="space-y-2" wire:show="!isEditingTitle">
                        <div class="flex items-center gap-2">
                            <flux:heading size="lg">{{ $task->title }}</flux:heading>
                            <flux:button size="xs" wire:click="$js.editTitle">Edit</flux:button>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($task->prioritized_at)
                                <flux:badge color="amber" size="lg" icon="star">Top priority</flux:badge>
                            @endif

                            @if ($task->completed_at)
                                <flux:badge color="green" size="lg" icon="check-circle">Completed</flux:badge>
                            @elseif ($task->closed_at)
                                <flux:badge color="zinc" size="lg" icon="x-circle">Closed</flux:badge>
                            @else
                                <flux:badge color="yellow" size="lg" icon="clock">Open</flux:badge>
                            @endif
                        </div>
                    </div>
                </div>

                <div>
                    <div class="flex gap-3">
                        <flux:avatar
                            circle
                            size="sm"
                            :src="$task->creator->profilePhotoUrl()"
                            name="{{ $task->creator->name }}"
                            color="auto"
                            color:seed="{{ $task->creator->id }}"
                            tooltip="{{ $task->creator->name }}"
                        />
                        <div class="flex-1 space-y-1">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-1">
                                    <div class="flex items-center gap-2">
                                        <flux:heading>{{ $task->creator->name }}</flux:heading>
                                        <flux:text class="text-xs">
                                            opened {{ $task->created_at->diffForHumans() }}
                                        </flux:text>
                                    </div>
                                </div>
                                <div class="flex gap-1">
                                    @if (Auth::user()->can('update', $task) && $task->description)
                                        <flux:button
                                            size="xs"
                                            wire:click="editDescription"
                                            wire:show="!isEditingDescription"
                                        >
                                            Edit
                                        </flux:button>
                                    @endif
                                </div>
                            </div>

                            @if ($isEditingDescription)
                                <form wire:submit="saveDescription" wire:show="isEditingDescription" wire:cloak>
                                    <div>
                                        <flux:composer
                                            wire:model="description"
                                            rows="6"
                                            max-rows="12"
                                            label="Task Description"
                                            label:sr-only
                                            placeholder="Add a detailed description..."
                                        >
                                            @if (count($this->images) > 0)
                                                <x-slot name="header">
                                                    <div class="flex flex-wrap gap-2">
                                                        @foreach ($this->images as $index => $image)
                                                            @if (is_object($image) && $image->isPreviewable())
                                                                <div
                                                                    class="relative overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700"
                                                                >
                                                                    <img
                                                                        src="{{ $image->temporaryUrl() }}"
                                                                        alt="Uploaded image"
                                                                        class="size-14"
                                                                    />
                                                                    <div class="absolute top-0 right-0 p-1">
                                                                        <button
                                                                            type="button"
                                                                            wire:click="removeImage({{ $index }})"
                                                                            class="flex items-center justify-center rounded-full bg-zinc-900/50 p-0.5 hover:bg-zinc-900/70"
                                                                        >
                                                                            <flux:icon
                                                                                icon="x-mark"
                                                                                variant="micro"
                                                                                class="text-white"
                                                                            />
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </x-slot>
                                            @endif

                                            <x-slot name="input">
                                                <flux:editor
                                                    variant="borderless"
                                                    toolbar="heading | bold italic | bullet ordered | link"
                                                    placeholder="Add a detailed description..."
                                                />
                                            </x-slot>
                                            <x-slot name="actionsLeading">
                                                <div>
                                                    <flux:file-upload wire:model="images" multiple>
                                                        <flux:button size="sm" variant="subtle" icon="paper-clip" />
                                                    </flux:file-upload>
                                                    <flux:error name="images" />
                                                </div>
                                            </x-slot>
                                            <x-slot name="actionsTrailing">
                                                <flux:button
                                                    type="button"
                                                    size="sm"
                                                    wire:click="$js.cancelEditDescription"
                                                >
                                                    Cancel
                                                </flux:button>
                                                <flux:button type="submit" size="sm" variant="primary">
                                                    Save
                                                </flux:button>
                                            </x-slot>
                                        </flux:composer>
                                    </div>
                                </form>
                            @endif

                            <div wire:show="!isEditingDescription" class="space-y-2">
                                @unless ($this->taskImages->isEmpty())
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($this->taskImages as $image)
                                            <img
                                                src="{{ $image->url() }}"
                                                alt="Task image"
                                                class="size-32 rounded-lg object-cover"
                                            />
                                        @endforeach
                                    </div>
                                @endunless

                                @if ($task->description)
                                    <x-prose>
                                        {!! $task->description !!}
                                    </x-prose>
                                @else
                                    <flux:button size="xs" wire:click="editDescription">Add description</flux:button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="space-y-2">
                        @unless ($this->checklistItems->isEmpty())
                            <flux:checkbox.group
                                label="Checklist"
                                wire:model.live="completedChecklistItems"
                                class="space-y-2"
                            >
                                @foreach ($this->checklistItems as $item)
                                    <div wire:key="{{ $item->id }}" class="flex items-center justify-between gap-3">
                                        <flux:field variant="inline">
                                            <flux:checkbox :value="$item->id" />
                                            <flux:label
                                                @class(['line-through' => in_array($item->id, $completedChecklistItems)])
                                            >
                                                {{ $item->content }}
                                            </flux:label>
                                        </flux:field>
                                        @if (Auth::user()->can('update', $task))
                                            <flux:dropdown position="bottom" align="end">
                                                <flux:button size="xs" icon="ellipsis-horizontal" variant="subtle" />
                                                <flux:menu>
                                                    <flux:menu.item
                                                        icon="trash"
                                                        wire:click="deleteChecklistItem({{ $item->id }})"
                                                    >
                                                        Delete
                                                    </flux:menu.item>
                                                </flux:menu>
                                            </flux:dropdown>
                                        @endif
                                    </div>
                                @endforeach
                            </flux:checkbox.group>
                        @endunless

                        <form wire:submit="addChecklist" wire:show="isAddingChecklistItem" wire:cloak>
                            <flux:composer
                                wire:model="newChecklistItem"
                                rows="1"
                                placeholder="New checklist item..."
                                submit="enter"
                                inline
                            >
                                <x-slot name="actionsTrailing">
                                    <flux:button type="button" size="sm" wire:click="$js.cancelAddingChecklistItem">
                                        Cancel
                                    </flux:button>
                                    <flux:button type="submit" size="sm" variant="primary">Add</flux:button>
                                </x-slot>
                            </flux:composer>
                        </form>
                        <flux:button
                            size="xs"
                            wire:click="$js.startAddingChecklistItem"
                            wire:show="!isAddingChecklistItem"
                        >
                            {{ $this->checklistItems->isEmpty() ? 'Add checklist' : 'Add checklist item' }}
                        </flux:button>
                    </div>
                </div>

                <flux:separator variant="subtle" />

                <!-- Comments Section -->
                <div class="space-y-4">
                    @unless ($this->comments->isEmpty())
                        <flux:heading>Comments</flux:heading>

                        <!-- Comments List -->
                        <div class="space-y-3">
                            @foreach ($this->comments as $comment)
                                <livewire:comment-item :comment="$comment" :key="$comment->id" />
                            @endforeach
                        </div>
                    @endunless

                    <!-- Add Comment Form -->
                    <div class="flex gap-3">
                        <flux:avatar
                            circle
                            size="sm"
                            :src="auth()->user()->profilePhotoUrl()"
                            name="{{ auth()->user()->name }}"
                            color="auto"
                            color:seed="{{ auth()->user()->id }}"
                            tooltip="{{ auth()->user()->name }}"
                        />
                        <form wire:submit="addComment" class="flex-1 space-y-3">
                            <flux:composer
                                wire:model="newComment"
                                rows="3"
                                max-rows="8"
                                label="Add a comment"
                                label:sr-only
                                placeholder="Write a comment..."
                            >
                                @if (count($this->commentImages) > 0)
                                    <x-slot name="header">
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($this->commentImages as $index => $image)
                                                @if (is_object($image) && $image->isPreviewable())
                                                    <div
                                                        class="relative overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700"
                                                    >
                                                        <img
                                                            src="{{ $image->temporaryUrl() }}"
                                                            alt="Uploaded image"
                                                            class="size-14"
                                                        />
                                                        <div class="absolute top-0 right-0 p-1">
                                                            <button
                                                                type="button"
                                                                wire:click="removeCommentImage({{ $index }})"
                                                                class="flex items-center justify-center rounded-full bg-zinc-900/50 p-0.5 hover:bg-zinc-900/70"
                                                            >
                                                                <flux:icon
                                                                    icon="x-mark"
                                                                    variant="micro"
                                                                    class="text-white"
                                                                />
                                                            </button>
                                                        </div>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </x-slot>
                                @endif

                                <x-slot name="input">
                                    <flux:editor
                                        variant="borderless"
                                        toolbar="bold italic | link"
                                        placeholder="Write a comment..."
                                    />
                                </x-slot>
                                <x-slot name="actionsLeading">
                                    <div>
                                        <flux:file-upload wire:model="commentImages" multiple>
                                            <flux:button size="sm" variant="subtle" icon="paper-clip" />
                                        </flux:file-upload>
                                        <flux:error name="commentImages" />
                                    </div>
                                </x-slot>
                                <x-slot name="actionsTrailing">
                                    <flux:button.group>
                                        @if ($task->completed_at || $task->closed_at)
                                            <flux:button type="button" wire:click="reopen" icon="arrow-path" size="sm">
                                                Reopen task
                                            </flux:button>
                                        @else
                                            <flux:button
                                                type="button"
                                                wire:click="complete"
                                                icon="check-circle"
                                                size="sm"
                                            >
                                                Complete task
                                            </flux:button>
                                        @endif
                                        <flux:dropdown>
                                            <flux:button icon="chevron-down" size="sm" />
                                            <flux:menu>
                                                @if ($task->completed_at || $task->closed_at)
                                                    <flux:menu.item
                                                        icon="arrow-path"
                                                        icon:variant="micro"
                                                        wire:click="reopen"
                                                    >
                                                        Reopen task
                                                    </flux:menu.item>
                                                @else
                                                    <flux:menu.item
                                                        icon="check-circle"
                                                        icon:variant="micro"
                                                        wire:click="complete"
                                                    >
                                                        Complete task
                                                    </flux:menu.item>
                                                    <flux:menu.item
                                                        icon="x-circle"
                                                        icon:variant="micro"
                                                        wire:click="close"
                                                    >
                                                        Close task
                                                    </flux:menu.item>
                                                @endif
                                            </flux:menu>
                                        </flux:dropdown>
                                    </flux:button.group>
                                    <flux:button type="submit" variant="primary" size="sm">Comment</flux:button>
                                </x-slot>
                            </flux:composer>
                        </form>
                    </div>
                </div>
            </div>

            <flux:separator variant="subtle" class="md:hidden" />

            <!-- Sidebar -->
            <div
                class="w-full flex-shrink-0 space-y-4 md:w-[260px] md:rounded-lg md:border md:border-zinc-200/50 md:bg-zinc-50/50 md:p-4 dark:md:border-zinc-800/50"
            >
                <flux:heading class="md:hidden">Details</flux:heading>

                <!-- Section, Tags, Assignees, and Subscribers Grid -->
                <div class="space-y-3">
                    <!-- Assignees Section -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <flux:heading class="text-xs">Assignees</flux:heading>
                            <flux:button
                                size="xs"
                                wire:click="$js.startManagingAssignees"
                                wire:show="!isManagingAssignees"
                            >
                                Edit
                            </flux:button>
                        </div>
                        <div class="space-y-2" wire:show="isManagingAssignees" wire:cloak>
                            <flux:pillbox
                                wire:model.live="selectedAssignees"
                                label="Assignees"
                                placeholder="Select assignees..."
                                size="sm"
                                multiple
                                label:sr-only
                            >
                                @foreach ($this->teamMembers as $member)
                                    <flux:pillbox.option :value="$member->id" wire:key="member-{{ $member->id }}">
                                        {{ $member->name }}
                                    </flux:pillbox.option>
                                @endforeach
                            </flux:pillbox>

                            <div>
                                <flux:button wire:click="$js.cancelManagingAssignees" size="sm">Done</flux:button>
                            </div>
                        </div>
                        <div class="space-y-2" wire:show="!isManagingAssignees">
                            @unless ($task->assignees->isEmpty())
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($task->assignees as $assignee)
                                        <flux:avatar
                                            circle
                                            size="sm"
                                            :src="$assignee->profilePhotoUrl()"
                                            name="{{ $assignee->name }}"
                                            color="auto"
                                            color:seed="{{ $assignee->id }}"
                                            tooltip="{{ $assignee->name }}"
                                        />
                                    @endforeach
                                </div>
                            @else
                                <flux:text class="text-xs">No one assigned</flux:text>
                            @endunless
                        </div>
                    </div>

                    <flux:separator variant="subtle" />

                    <!-- Section Section -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <flux:heading class="text-xs">Section</flux:heading>
                            <flux:button size="xs" wire:click="$js.startManagingSection" wire:show="!isManagingSection">
                                Edit
                            </flux:button>
                        </div>
                        <div class="space-y-2" wire:show="isManagingSection" wire:cloak>
                            <flux:select
                                variant="listbox"
                                searchable
                                wire:model.live="selectedSection"
                                label="Section"
                                placeholder="Select a section..."
                                size="sm"
                                label:sr-only
                            >
                                <flux:select.option value="">No section</flux:select.option>
                                @foreach ($this->projectSections as $section)
                                    <flux:select.option :value="$section->id" wire:key="section-{{ $section->id }}">
                                        {{ $section->title }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>

                            <div>
                                <flux:button wire:click="$js.cancelManagingSection" size="sm">Done</flux:button>
                            </div>
                        </div>
                        <div class="space-y-2" wire:show="!isManagingSection">
                            @if ($task->section)
                                <flux:badge size="sm">{{ $task->section->title }}</flux:badge>
                            @else
                                <flux:text class="text-xs">No section assigned</flux:text>
                            @endif
                        </div>
                    </div>

                    <flux:separator variant="subtle" />

                    <!-- Priority Section -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <flux:heading class="text-xs">Priority</flux:heading>
                        </div>
                        @if ($task->prioritized_at)
                            <flux:button size="xs" wire:click="togglePriority" icon="x-mark">Not urgent</flux:button>
                            <flux:text class="text-xs">This task is marked as top priority.</flux:text>
                        @else
                            <flux:button size="xs" wire:click="togglePriority" icon="star">Top priority</flux:button>
                            <flux:text class="text-xs">Mark this task as top priority.</flux:text>
                        @endif
                    </div>

                    <flux:separator variant="subtle" />

                    <!-- Tags Section -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <flux:heading class="text-xs">Tags</flux:heading>
                            <flux:button size="xs" wire:click="$js.startManagingTags" wire:show="!isManagingTags">
                                Edit
                            </flux:button>
                        </div>
                        <div class="space-y-2" wire:show="isManagingTags" wire:cloak>
                            <flux:pillbox
                                wire:model.live="selectedTags"
                                variant="combobox"
                                label="Tags"
                                placeholder="Select tags..."
                                size="sm"
                                multiple
                                label:sr-only
                            >
                                <x-slot name="input">
                                    <flux:pillbox.input wire:model="tagSearch" placeholder="Search or create tags..." />
                                </x-slot>

                                @foreach ($this->teamTags as $tag)
                                    <flux:pillbox.option :value="$tag->id" wire:key="tag-{{ $tag->id }}">
                                        {{ $tag->name }}
                                    </flux:pillbox.option>
                                @endforeach

                                <flux:pillbox.option.create wire:click="addTag" min-length="2">
                                    Create "
                                    <span wire:text="tagSearch"></span>
                                    "
                                </flux:pillbox.option.create>
                            </flux:pillbox>

                            <div>
                                <flux:button wire:click="$js.cancelManagingTags" size="sm">Done</flux:button>
                            </div>
                        </div>
                        <div class="space-y-2" wire:show="!isManagingTags">
                            @unless ($task->tags->isEmpty())
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($task->tags as $tag)
                                        <flux:badge size="sm">{{ $tag->name }}</flux:badge>
                                    @endforeach
                                </div>
                            @else
                                <flux:text class="text-xs">No tags assigned</flux:text>
                            @endunless
                        </div>
                    </div>

                    <flux:separator variant="subtle" />

                    <!-- Subscribers Section -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <flux:heading class="text-xs">Notifications</flux:heading>
                        </div>
                        @if ($task->isSubscribed(auth()->user()))
                            <flux:button size="xs" wire:click="toggleSubscribe" icon="bell-slash">
                                Unsubscribe
                            </flux:button>
                            <flux:text class="text-xs">
                                You're receiving notifications because you're subscribed to this task.
                            </flux:text>
                        @else
                            <flux:button size="xs" wire:click="toggleSubscribe" icon="bell">Subscribe</flux:button>
                            <flux:text class="text-xs">You're not receiving notifications from this task.</flux:text>
                        @endif
                    </div>

                    <flux:separator variant="subtle" />

                    <!-- Saved Section -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <flux:heading class="text-xs">Saved</flux:heading>
                        </div>
                        @if ($task->isSaved(auth()->user()))
                            <flux:button size="xs" wire:click="toggleSaved" icon="x-mark">Unsave</flux:button>
                            <flux:text class="text-xs">You've saved this task for later reference.</flux:text>
                        @else
                            <flux:button size="xs" wire:click="toggleSaved" icon="bookmark">Save</flux:button>
                            <flux:text class="text-xs">Save this task for later reference.</flux:text>
                        @endif
                    </div>

                    <flux:separator variant="subtle" />

                    <flux:modal.trigger :name="'delete-task-' . $task->id">
                        <flux:button size="xs" variant="subtle" icon="trash">Delete task</flux:button>
                    </flux:modal.trigger>
                </div>
            </div>
        </div>
    </div>
    <flux:modal :name="'delete-task-' . $task->id" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete task?</flux:heading>
                <flux:text class="mt-2">You're about to delete this task. This action cannot be reversed.</flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="danger" wire:click="$parent.$parent.deleteTask({{ $task->id }})">
                    Delete task
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>

<script>
    this.$js.editTitle = () => {
        this.isEditingTitle = true;
    };

    this.$js.cancelEditDescription = () => {
        this.isEditingDescription = false;
        this.description = '';
        this.images = [];
    };

    this.$js.startAddingChecklistItem = () => {
        this.isAddingChecklistItem = true;
    };

    this.$js.cancelAddingChecklistItem = () => {
        this.isAddingChecklistItem = false;
        this.newChecklistItem = '';
    };

    this.$js.startManagingTags = () => {
        this.isManagingTags = true;
    };

    this.$js.cancelManagingTags = () => {
        this.isManagingTags = false;
        this.tagSearch = '';
    };

    this.$js.startManagingSection = () => {
        this.isManagingSection = true;
    };

    this.$js.cancelManagingSection = () => {
        this.isManagingSection = false;
    };

    this.$js.startManagingAssignees = () => {
        this.isManagingAssignees = true;
    };

    this.$js.cancelManagingAssignees = () => {
        this.isManagingAssignees = false;
    };
</script>
