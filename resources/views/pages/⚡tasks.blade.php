<?php

use App\Models\Team;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public Team $team;

    #[Url(as: 'projects')]
    public array $selectedProjects = [];

    #[Url(as: 'tags')]
    public array $selectedTags = [];

    #[Url(as: 'added_by')]
    public array $selectedAddedBy = [];

    #[Url(as: 'closed_by')]
    public array $selectedClosedBy = [];

    #[Url(as: 'assigned_to')]
    public array $selectedAssignedTo = [];

    #[Url(as: 'sort')]
    public string $sortBy = 'recently_updated';

    #[Url(as: 'search')]
    public ?string $search = null;

    public int $perPage = 15;

    #[Computed]
    public function tasks()
    {
        return $this->team
            ->tasks()
            ->with(['creator', 'completer', 'closer', 'project', 'tags', 'comments', 'assignees'])
            ->when(! empty($this->selectedProjects), function ($query) {
                $query->whereIn('project_id', $this->selectedProjects);
            })
            ->when(! empty($this->selectedTags), function ($query) {
                $query->whereHas('tags', function ($q) {
                    $q->whereIn('tags.id', $this->selectedTags);
                });
            })
            ->when(! empty($this->selectedAddedBy), function ($query) {
                $query->whereIn('user_id', $this->selectedAddedBy);
            })
            ->when(! empty($this->selectedClosedBy), function ($query) {
                $query->whereIn('completed_by', $this->selectedClosedBy);
            })
            ->when(! empty($this->selectedAssignedTo), function ($query) {
                $query->whereHas('assignees', function ($q) {
                    $q->whereIn('users.id', $this->selectedAssignedTo);
                });
            })
            ->when($this->search, function ($query) {
                $query->where('title', 'like', '%' . $this->search . '%');
            })
            ->tap(function ($query) {
                switch ($this->sortBy) {
                    case 'oldest':
                        $query->oldest();
                        break;
                    case 'recently_updated':
                        $query->latest('updated_at');
                        break;
                    case 'newest':
                    default:
                        $query->latest();
                        break;
                }
            })
            ->paginate($this->perPage);
    }

    #[Computed]
    public function projects()
    {
        return $this->team
            ->projects()
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function tags()
    {
        return $this->team
            ->tags()
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function teamMembers()
    {
        return $this->team->allUsers();
    }

    public function clearFilters()
    {
        $this->selectedProjects = [];
        $this->selectedTags = [];
        $this->selectedAddedBy = [];
        $this->selectedClosedBy = [];
        $this->selectedAssignedTo = [];
        $this->sortBy = 'recently_updated';
        $this->search = null;
        $this->resetPage();
    }

    public function updatedSelectedProjects()
    {
        $this->resetPage();
    }

    public function updatedSelectedTags()
    {
        $this->resetPage();
    }

    public function updatedSelectedAddedBy()
    {
        $this->resetPage();
    }

    public function updatedSelectedClosedBy()
    {
        $this->resetPage();
    }

    public function updatedSelectedAssignedTo()
    {
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    #[Computed]
    public function activeFiltersCount(): int
    {
        return count($this->selectedProjects) +
            count($this->selectedTags) +
            count($this->selectedAddedBy) +
            count($this->selectedClosedBy) +
            count($this->selectedAssignedTo) +
            ($this->sortBy !== 'recently_updated' ? 1 : 0) +
            ($this->search ? 1 : 0);
    }
};
?>

<div>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-1">
                <flux:heading level="1" size="lg">All tasks</flux:heading>
                @if ($this->activeFiltersCount > 0)
                    <flux:badge wire:click="clearFilters" as="button" icon:trailing="x-mark" size="sm">
                        {{ $this->activeFiltersCount }} {{ Str::plural('filter', $this->activeFiltersCount) }}
                    </flux:badge>
                @endif
            </div>
            <div class="flex items-center gap-1">
                <flux:modal.trigger name="search" shortcut="cmd.k">
                    <flux:input as="button" placeholder="Search..." icon="magnifying-glass" size="sm" />
                </flux:modal.trigger>

                <flux:modal
                    name="search"
                    variant="bare"
                    class="my-[12vh] max-h-screen w-full max-w-[30rem] overflow-y-hidden"
                >
                    <flux:command class="inline-flex max-h-[76vh] flex-col border-none shadow-lg">
                        <flux:command.input placeholder="Search..." wire:model.live="search" closable />

                        <flux:command.items>
                            @foreach ($this->tasks->take(10) as $task)
                                <flux:command.item
                                    x-on:click="$flux.modal('task-{{ $task->id }}').show()"
                                    wire:key="task-{{ $task->id }}"
                                >
                                    {{ $task->title }}
                                </flux:command.item>
                            @endforeach
                        </flux:command.items>
                    </flux:command>
                </flux:modal>

                <flux:dropdown align="end">
                    <flux:button icon="adjustments-horizontal" icon:trailing="chevron-down" size="sm">
                        Filter
                    </flux:button>

                    <flux:menu keep-open>
                        <flux:menu.submenu heading="Projects">
                            <flux:menu.checkbox.group wire:model.live="selectedProjects">
                                @foreach ($this->projects as $project)
                                    <flux:menu.checkbox :value="$project->id" keep-open>
                                        {{ $project->name }}
                                    </flux:menu.checkbox>
                                @endforeach
                            </flux:menu.checkbox.group>
                        </flux:menu.submenu>

                        <flux:menu.submenu heading="Tags">
                            <flux:menu.checkbox.group wire:model.live="selectedTags">
                                @foreach ($this->tags as $tag)
                                    <flux:menu.checkbox :value="$tag->id" keep-open>
                                        {{ $tag->name }}
                                    </flux:menu.checkbox>
                                @endforeach
                            </flux:menu.checkbox.group>
                        </flux:menu.submenu>

                        <flux:menu.submenu heading="Added by">
                            <flux:menu.checkbox.group wire:model.live="selectedAddedBy">
                                @foreach ($this->teamMembers as $member)
                                    <flux:menu.checkbox :value="$member->id" keep-open>
                                        {{ $member->name }}
                                    </flux:menu.checkbox>
                                @endforeach
                            </flux:menu.checkbox.group>
                        </flux:menu.submenu>

                        <flux:menu.submenu heading="Closed by">
                            <flux:menu.checkbox.group wire:model.live="selectedClosedBy">
                                @foreach ($this->teamMembers as $member)
                                    <flux:menu.checkbox :value="$member->id" keep-open>
                                        {{ $member->name }}
                                    </flux:menu.checkbox>
                                @endforeach
                            </flux:menu.checkbox.group>
                        </flux:menu.submenu>

                        <flux:menu.submenu heading="Assigned to">
                            <flux:menu.checkbox.group wire:model.live="selectedAssignedTo">
                                @foreach ($this->teamMembers as $member)
                                    <flux:menu.checkbox :value="$member->id" keep-open>
                                        {{ $member->name }}
                                    </flux:menu.checkbox>
                                @endforeach
                            </flux:menu.checkbox.group>
                        </flux:menu.submenu>

                        <flux:menu.separator />

                        <flux:menu.submenu heading="Sort">
                            <flux:menu.radio.group wire:model.live="sortBy" keep-open>
                                <flux:menu.radio value="newest">Newest</flux:menu.radio>
                                <flux:menu.radio value="oldest">Oldest</flux:menu.radio>
                                <flux:menu.radio value="recently_updated">Recently updated</flux:menu.radio>
                            </flux:menu.radio.group>
                        </flux:menu.submenu>

                        <flux:menu.separator />

                        <flux:menu.item wire:click="clearFilters" variant="danger">Clear all</flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            </div>
        </div>

        @if ($this->tasks->isEmpty())
            <div class="py-12 text-center text-gray-500">
                <p>No tasks found matching your filters.</p>
                @if ($this->activeFiltersCount > 0)
                    <flux:button wire:click="clearFilters" size="sm" variant="outline" class="mt-2">
                        Clear filters
                    </flux:button>
                @endif
            </div>
        @else
            <div class="border-t border-zinc-800/5 dark:border-white/10">
                @foreach ($this->tasks as $task)
                    <div wire:key="task-{{ $task->id }}">
                        <flux:modal.trigger name="task-{{ $task->id }}">
                            <x-list-item as="button">
                                <x-slot name="heading">
                                    <div class="flex w-full justify-between">
                                        <div class="space-y-2">
                                            <div class="font-medium">{{ $task->title }}</div>
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                @unless ($task->tags->isEmpty())
                                                    <div class="flex items-center gap-1">
                                                        @foreach ($task->tags->take(3) as $tag)
                                                            <flux:badge size="sm">{{ $tag->name }}</flux:badge>
                                                        @endforeach

                                                        @if ($task->tags->count() > 3)
                                                            <flux:badge size="sm">
                                                                +{{ $task->tags->count() - 3 }}
                                                            </flux:badge>
                                                        @endif
                                                    </div>

                                                    <flux:text class="text-xs">·</flux:text>
                                                @endunless

                                                @if ($task->project)
                                                    <flux:text class="font-mono text-xs">
                                                        {{ $task->project->handle }}-{{ $task->number }}
                                                    </flux:text>

                                                    <flux:text class="text-xs">·</flux:text>
                                                @endif

                                                @unless ($task->completed_at || $task->closed_at)
                                                    <div class="flex items-center gap-2">
                                                        <flux:avatar
                                                            circle
                                                            size="xs"
                                                            :src="$task->creator->profilePhotoUrl()"
                                                            name="{{ $task->creator->name }}"
                                                            color="auto"
                                                            color:seed="{{ $task->creator->id }}"
                                                            tooltip="{{ $task->creator->name }}"
                                                        />
                                                        <flux:text class="text-xs">
                                                            opened
                                                            {{ $task->created_at->diffForHumans() }}
                                                        </flux:text>
                                                    </div>
                                                @endunless

                                                @if ($task->completed_at && $task->completer)
                                                    <flux:text class="text-xs">·</flux:text>
                                                    <div class="flex items-center gap-2">
                                                        <flux:avatar
                                                            circle
                                                            size="xs"
                                                            :src="$task->completer->profilePhotoUrl()"
                                                            name="{{ $task->completer->name }}"
                                                            color="auto"
                                                            color:seed="{{ $task->completer->id }}"
                                                            tooltip="{{ $task->completer->name }}"
                                                        />
                                                        <flux:text class="text-xs">
                                                            completed
                                                            {{ $task->completed_at->diffForHumans() }}
                                                        </flux:text>
                                                    </div>
                                                @endif

                                                @if ($task->closed_at && $task->closer)
                                                    <flux:text class="text-xs">·</flux:text>
                                                    <div class="flex items-center gap-2">
                                                        <flux:avatar
                                                            circle
                                                            size="xs"
                                                            :src="$task->closer->profilePhotoUrl()"
                                                            name="{{ $task->closer->name }}"
                                                            color="auto"
                                                            color:seed="{{ $task->closer->id }}"
                                                            tooltip="{{ $task->closer->name }}"
                                                        />
                                                        <flux:text class="text-xs">
                                                            closed
                                                            {{ $task->closed_at->diffForHumans() }}
                                                        </flux:text>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-3">
                                            @unless ($task->comments->isEmpty())
                                                <flux:text class="inline-flex gap-1 text-xs">
                                                    <flux:icon.chat-bubble-bottom-center-text variant="micro" />
                                                    {{ $task->comments->count() }}
                                                </flux:text>
                                            @endunless

                                            <flux:avatar.group>
                                                @foreach ($task->assignees->take(3) as $assignee)
                                                    <flux:avatar
                                                        circle
                                                        size="xs"
                                                        :src="$assignee->profilePhotoUrl()"
                                                        name="{{ $assignee->name }}"
                                                        color="auto"
                                                        color:seed="{{ $assignee->id }}"
                                                        tooltip="{{ $assignee->name }}"
                                                    />
                                                @endforeach

                                                @if ($task->assignees->count() > 3)
                                                    <flux:avatar circle size="xs">
                                                        {{ $task->assignees->count() }}+
                                                    </flux:avatar>
                                                @endif
                                            </flux:avatar.group>
                                        </div>
                                    </div>
                                </x-slot>
                            </x-list-item>
                        </flux:modal.trigger>

                        <flux:modal name="task-{{ $task->id }}" class="w-full max-w-[95vw] lg:max-w-150">
                            <livewire:task :task="$task" wire:key="task-{{ $task->id }}" lazy />
                        </flux:modal>

                        @unless ($loop->last)
                            <flux:separator variant="subtle" />
                        @endunless
                    </div>
                @endforeach
            </div>

            <flux:pagination :paginator="$this->tasks" class="mt-4" />
        @endif
    </div>
</div>
