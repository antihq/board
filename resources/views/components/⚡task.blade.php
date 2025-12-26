<?php

use App\Models\Task;
use App\Notifications\TaskClosed;
use App\Notifications\TaskCommented;
use App\Notifications\TaskReopened;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public Task $task;

    public bool $showModal = false;

    public string $description = '';

    public bool $isEditingDescription = false;

    public bool $isEditingTitle = false;

    public string $title = '';

    public string $newComment = '';

    public ?int $editingCommentId = null;

    public string $editingCommentContent = '';

    public bool $isAddingChecklistItem = false;

    public string $newChecklistItemContent = '';

    public array $completedChecklistItems = [];

    public array $selectedTags = [];

    public string $tagSearch = '';

    public bool $isManagingTags = false;

    public bool $isManagingSection = false;

    public ?int $selectedSection = null;

    public bool $isManagingAssignees = false;

    public array $selectedAssignees = [];

    public function mount()
    {
        $this->completedChecklistItems = $this->task
            ->checklistItems()
            ->where('completed', true)
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

    public function openModal()
    {
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->isEditingDescription = false;
        $this->isAddingChecklistItem = false;
        $this->newChecklistItemContent = '';
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

        $this->dispatch('task.updated');

        $this->isEditingTitle = false;
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

        $this->dispatch('task.updated');

        $this->isEditingDescription = false;
    }

    public function cancelEdit()
    {
        $this->isEditingDescription = false;
        $this->description = '';
    }

    public function startAddingChecklistItem()
    {
        $this->isAddingChecklistItem = true;
        $this->newChecklistItemContent = '';
    }

    public function saveChecklistItem()
    {
        $this->validate([
            'newChecklistItemContent' => 'required|string|max:500',
        ]);

        $this->task->checklistItems()->create([
            'content' => $this->pull('newChecklistItemContent'),
            'completed' => false,
        ]);

        $this->task->touch();
    }

    public function cancelAddingChecklistItem()
    {
        $this->isAddingChecklistItem = false;
        $this->newChecklistItemContent = '';
    }

    public function updatedCompletedChecklistItems()
    {
        $this->task
            ->checklistItems()
            ->whereIn('id', $this->completedChecklistItems)
            ->where('completed', false)
            ->update(['completed' => true]);

        $this->task
            ->checklistItems()
            ->whereNotIn('id', $this->completedChecklistItems)
            ->where('completed', true)
            ->update(['completed' => false]);

        $this->task->touch();

        $this->dispatch('task.updated');
    }

    public function addComment()
    {
        $this->validate([
            'newComment' => 'required|string|max:5000',
        ]);

        $comment = $this->task->comments()->create([
            'user_id' => Auth::id(),
            'content' => $this->pull('newComment'),
        ]);

        $this->task->touch();

        $this->task->subscribers()->syncWithoutDetaching(Auth::id());

        $this->task->subscribers
            ->where('id', '!=', Auth::id())
            ->each(
                fn ($subscriber) => $subscriber->notify(
                    new TaskCommented($comment->load('user', 'task.project', 'task.team')),
                ),
            );

        $this->dispatch('task.updated');
    }

    public function closeTask()
    {
        $this->task->update([
            'completed_at' => now(),
            'completed_by' => Auth::id(),
            'reopened_at' => null,
            'reopened_by' => null,
        ]);

        $this->task->touch();

        $this->task->subscribers
            ->where('id', '!=', Auth::id())
            ->each(fn ($subscriber) => $subscriber->notify(new TaskClosed($this->task->load('project', 'team'))));

        $this->dispatch('task.updated');
    }

    public function reopenTask()
    {
        $this->task->update([
            'completed_at' => null,
            'completed_by' => null,
            'reopened_at' => now(),
            'reopened_by' => Auth::id(),
        ]);

        $this->task->touch();

        $this->task->subscribers
            ->where('id', '!=', Auth::id())
            ->each(fn ($subscriber) => $subscriber->notify(new TaskReopened($this->task->load('project', 'team'))));

        $this->dispatch('task.updated');
    }

    public function startManagingTags()
    {
        $this->isManagingTags = true;
    }

    public function cancelManagingTags()
    {
        $this->isManagingTags = false;
        $this->tagSearch = '';
    }

    public function startManagingSection()
    {
        $this->isManagingSection = true;
    }

    public function cancelManagingSection()
    {
        $this->isManagingSection = false;
    }

    public function startManagingAssignees()
    {
        $this->isManagingAssignees = true;
    }

    public function cancelManagingAssignees()
    {
        $this->isManagingAssignees = false;
    }

    public function updatedSelectedAssignees()
    {
        // Authorize that user can manage task assignees
        $this->authorize('manageAssignees', $this->task);

        // Get team members and team owner
        $teamUsers = $this->task->team->users()->get();
        $allPossibleAssignees = $teamUsers->push($this->task->team->owner);

        // Filter to only include valid assignees
        $validAssignees = $allPossibleAssignees->whereIn('id', $this->selectedAssignees);

        $this->task->assignees()->sync($validAssignees->pluck('id'));

        $this->task->subscribers()->syncWithoutDetaching($validAssignees->pluck('id'));

        $this->task->touch();

        $this->dispatch('task.updated');
    }

    public function updatedSelectedSection()
    {
        $section = $this->selectedSection ? $this->task->project->sections()->findOrFail($this->selectedSection) : null;

        $this->task->update([
            'section_id' => $section?->id,
            'section_moved_at' => now(),
            'section_moved_by' => Auth::id(),
        ]);

        $this->task->touch();

        $this->dispatch('task.updated');
    }

    public function createTag()
    {
        $this->validate([
            'tagSearch' => 'required|string|max:255',
        ]);

        $tag = $this->task->team->tags()->create([
            'name' => $this->pull('tagSearch'),
        ]);

        $this->task->tags()->attach($tag->id);
        $this->selectedTags[] = $tag->id;
        $this->tagSearch = '';

        $this->task->touch();

        $this->dispatch('task.updated');
    }

    public function updatedSelectedTags()
    {
        $tags = $this->task->team->tags()->findMany($this->selectedTags);
        $this->task->tags()->sync($tags->pluck('id'));

        $this->task->touch();

        $this->dispatch('task.updated');
    }

    public function togglePriority()
    {
        if ($this->task->prioritized_at) {
            $this->task->update([
                'prioritized_at' => null,
                'prioritized_by' => null,
            ]);
        } else {
            $this->task->update([
                'prioritized_at' => now(),
                'prioritized_by' => Auth::id(),
            ]);
        }

        $this->task->touch();

        $this->dispatch('task.updated');
    }

    public function deleteTask()
    {
        $this->authorize('delete', $this->task->project);

        $this->task->comments()->delete();
        $this->task->checklistItems()->delete();
        $this->task->tags()->detach();
        $this->task->delete();

        $this->dispatch('task-deleted', taskId: $this->task->id);
    }

    public function toggleSubscribe()
    {
        $this->authorize('update', $this->task);

        $userId = Auth::id();

        if (
            $this->task
                ->subscribers()
                ->where('user_id', $userId)
                ->exists()
        ) {
            $this->task->subscribers()->detach($userId);
        } else {
            $this->task->subscribers()->attach($userId);
        }
    }

    public function toggleSaved()
    {
        $this->authorize('update', $this->task);

        $userId = Auth::id();
        $teamId = $this->task->team_id;

        if (
            $this->task
                ->savers()
                ->where('user_id', $userId)
                ->exists()
        ) {
            $this->task->savers()->detach($userId);
        } else {
            $this->task->savers()->attach($userId, ['team_id' => $teamId]);
        }
    }

    public function deleteComment($commentId)
    {
        $comment = $this->task->comments()->findOrFail($commentId);

        $this->authorize('delete', $comment);

        $comment->delete();

        $this->task->touch();

        $this->dispatch('task.updated');
    }

    public function startEditingComment($commentId)
    {
        $comment = $this->task->comments()->findOrFail($commentId);

        $this->authorize('update', $comment);

        $this->editingCommentId = $commentId;
        $this->editingCommentContent = $comment->content;
    }

    public function saveComment()
    {
        $this->validate([
            'editingCommentContent' => 'required|string|max:5000',
        ]);

        $comment = $this->task->comments()->findOrFail($this->editingCommentId);

        $this->authorize('update', $comment);

        $comment->update([
            'content' => $this->pull('editingCommentContent'),
            'edited_by' => Auth::id(),
            'edited_at' => now(),
        ]);

        $this->task->touch();

        $this->editingCommentId = null;
        $this->dispatch('task.updated');
    }

    public function cancelEditingComment()
    {
        $this->editingCommentId = null;
        $this->editingCommentContent = '';
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
            ->with('user', 'editor')
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
        $teamUsers = $this->task->team
            ->users()
            ->orderBy('name')
            ->get();

        // Include team owner if not already in the list
        $owner = $this->task->team->owner;
        if (! $teamUsers->contains('id', $owner->id)) {
            $teamUsers = $teamUsers->push($owner);
        }

        return $teamUsers->sortBy('name')->values();
    }

    #[Computed]
    public function subscribers()
    {
        return $this->task->subscribers;
    }

    #[Computed]
    public function isSaved()
    {
        return $this->task
            ->savers()
            ->where('user_id', Auth::id())
            ->exists();
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
                <div class="flex flex-wrap items-center gap-2">
                    @if ($task->prioritized_at)
                        <flux:badge color="amber" size="lg" icon="star">Top priority</flux:badge>
                    @endif

                    @if ($task->completed_at)
                        <flux:badge color="purple" size="lg" icon="check-circle">Closed</flux:badge>
                    @else
                        <flux:badge color="green" size="lg" icon="clock">Open</flux:badge>
                    @endif
                </div>
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

    <div>
        <div class="space-y-2">
            @unless ($this->checklistItems->isEmpty())
                <flux:checkbox.group label="Checklist" wire:model.live="completedChecklistItems">
                    @foreach ($this->checklistItems as $item)
                        <flux:field variant="inline" wire:key="{{ $item->id }}">
                            <flux:checkbox :value="$item->id" />
                            <flux:label @class(['line-through' => in_array($item->id, $completedChecklistItems)])>
                                {{ $item->content }}
                            </flux:label>
                        </flux:field>
                    @endforeach
                </flux:checkbox.group>
            @endunless

            @if ($this->isAddingChecklistItem)
                <form wire:submit="saveChecklistItem">
                    <flux:composer
                        wire:model="newChecklistItemContent"
                        rows="1"
                        placeholder="New checklist item..."
                        submit="enter"
                        inline
                    >
                        <x-slot name="actionsTrailing">
                            <flux:button type="button" size="sm" wire:click="cancelAddingChecklistItem">
                                Cancel
                            </flux:button>
                            <flux:button type="submit" size="sm" variant="primary" color="green">Add</flux:button>
                        </x-slot>
                    </flux:composer>
                </form>
            @else
                <flux:button size="xs" wire:click="startAddingChecklistItem">
                    {{ $this->checklistItems->isEmpty() ? 'Add checklist' : 'Add checklist item' }}
                </flux:button>
            @endif
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
                    <div class="flex gap-3">
                        <flux:avatar
                            circle
                            size="sm"
                            :src="$comment->user->profilePhotoUrl()"
                            name="{{ $comment->user->name }}"
                            color="auto"
                            color:seed="{{ $comment->user->id }}"
                            tooltip="{{ $comment->user->name }}"
                        />
                        <div class="flex-1 space-y-1">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-1">
                                    <div class="flex items-center gap-2">
                                        <flux:heading>{{ $comment->user->name }}</flux:heading>
                                        <flux:text class="text-xs">
                                            {{ $comment->created_at->diffForHumans() }}
                                        </flux:text>
                                    </div>
                                    @if ($comment->edited_at)
                                        <div class="flex items-center gap-1">
                                            <flux:text>·</flux:text>
                                            <flux:text class="text-xs">
                                                edited by {{ $comment->editor->name }}
                                            </flux:text>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex gap-1">
                                    @if (Auth::user()->can('update', $comment) || Auth::user()->can('delete', $comment))
                                        <flux:dropdown position="bottom" align="end">
                                            <flux:button size="xs" icon="ellipsis-horizontal" variant="subtle" />
                                            <flux:menu>
                                                @if (Auth::user()->can('update', $comment))
                                                    <flux:menu.item icon="pencil" wire:click="startEditingComment({{ $comment->id }})">
                                                        Edit
                                                    </flux:menu.item>
                                                @endif
                                                @if (Auth::user()->can('delete', $comment))
                                                    <flux:modal.trigger :name="'delete-comment-' . $comment->id">
                                                        <flux:menu.item variant="danger" icon="trash">
                                                            Delete
                                                        </flux:menu.item>
                                                    </flux:modal.trigger>
                                                @endif
                                            </flux:menu>
                                        </flux:dropdown>
                                    @endif
                                </div>
                            </div>
                            @if ($editingCommentId === $comment->id)
                                <form wire:submit="saveComment" class="space-y-2">
                                    <flux:composer
                                        wire:model="editingCommentContent"
                                        rows="3"
                                        max-rows="8"
                                        placeholder="Edit your comment..."
                                    >
                                        <x-slot name="input">
                                            <flux:editor variant="borderless" toolbar="bold italic | link" placeholder="Edit your comment..." />
                                        </x-slot>
                                        <x-slot name="actionsLeading">
                                            <!-- ... -->
                                        </x-slot>
                                        <x-slot name="actionsTrailing">
                                            <flux:button type="button" size="sm" variant="subtle" wire:click="cancelEditingComment">Cancel</flux:button>
                                            <flux:button type="submit" size="sm" variant="primary" color="green">Update comment</flux:button>
                                        </x-slot>
                                    </flux:composer>
                                </form>
                            @else
                                <div class="prose prose-sm prose-zinc dark:prose-invert max-w-none">
                                    {!! $comment->content !!}
                                </div>
                            @endif
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
                    <flux:editor variant="borderless" toolbar="bold italic | link" placeholder="Write a comment..." />
                </x-slot>
                <x-slot name="actionsLeading"></x-slot>
                <x-slot name="actionsTrailing">
                    @unless ($task->completed_at)
                        <flux:button type="button" size="sm" wire:click="closeTask">Close task</flux:button>
                    @else
                        <flux:button type="button" size="sm" wire:click="reopenTask">Reopen task</flux:button>
                    @endunless
                    <flux:button type="submit" size="sm" variant="primary" color="green">Comment</flux:button>
                </x-slot>
            </flux:composer>
        </form>
    </div>

    <flux:separator variant="subtle" />

    <!-- Section, Tags, Assignees, and Subscribers Grid -->
    <div class="space-y-3">
        <!-- Assignees Section -->
        <div class="space-y-2">
            <div class="flex items-center justify-between gap-2">
                <flux:heading class="text-xs">Assignees</flux:heading>
                @unless ($this->isManagingAssignees)
                    <flux:button size="xs" wire:click="startManagingAssignees">Edit</flux:button>
                @endunless
            </div>
            @if ($this->isManagingAssignees)
                <div class="space-y-2">
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
                        <flux:button wire:click="cancelManagingAssignees" size="sm">Done</flux:button>
                    </div>
                </div>
            @else
                <div class="space-y-2">
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
            @endif
        </div>

        <flux:separator variant="subtle" />

        <!-- Section Section -->
        <div class="space-y-2">
            <div class="flex items-center justify-between gap-2">
                <flux:heading class="text-xs">Section</flux:heading>
                @unless ($this->isManagingSection)
                    <flux:button size="xs" wire:click="startManagingSection">Edit</flux:button>
                @endunless
            </div>
            @if ($this->isManagingSection)
                <div class="space-y-2">
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
                        <flux:button wire:click="cancelManagingSection" size="sm">Done</flux:button>
                    </div>
                </div>
            @else
                <div class="space-y-2">
                    @if ($task->section)
                        <flux:badge size="sm">{{ $task->section->title }}</flux:badge>
                    @else
                        <flux:text class="text-xs">No section assigned</flux:text>
                    @endif
                </div>
            @endif
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
                @unless ($this->isManagingTags)
                    <flux:button size="xs" wire:click="startManagingTags">Edit</flux:button>
                @endunless
            </div>
            @if ($this->isManagingTags)
                <div class="space-y-2">
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

                        <flux:pillbox.option.create wire:click="createTag" min-length="2">
                            Create "
                            <span wire:text="tagSearch"></span>
                            "
                        </flux:pillbox.option.create>
                    </flux:pillbox>

                    <div>
                        <flux:button wire:click="cancelManagingTags" size="sm">Done</flux:button>
                    </div>
                </div>
            @else
                <div class="space-y-2">
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
            @endif
        </div>

        <flux:separator variant="subtle" />

        <!-- Subscribers Section -->
        <div class="space-y-2">
            <div class="flex items-center justify-between gap-2">
                <flux:heading class="text-xs">Notifications</flux:heading>
            </div>
            @if ($this->subscribers->contains('id', auth()->id()))
                <flux:button size="xs" wire:click="toggleSubscribe" icon="bell-slash">Unsubscribe</flux:button>
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
            @if ($this->isSaved)
                <flux:button size="xs" wire:click="toggleSaved" icon="x-mark">Unsave</flux:button>
                <flux:text class="text-xs">You've saved this task for later reference.</flux:text>
            @else
                <flux:button size="xs" wire:click="toggleSaved" icon="bookmark">Save</flux:button>
                <flux:text class="text-xs">Save this task for later reference.</flux:text>
            @endif
        </div>
    </div>

    <flux:separator variant="subtle" />

    <flux:modal.trigger :name="'delete-task-' . $task->id">
        <flux:button size="xs" variant="subtle" icon="trash">Delete task</flux:button>
    </flux:modal.trigger>

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
                <flux:button type="submit" variant="danger" wire:click="deleteTask">Delete task</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
