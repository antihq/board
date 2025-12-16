<?php

use App\Models\Task;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Tasks')] class extends Component
{
    public Team $team;

    #[Url(as: 'projects', except: '')]
    public array $selectedProjects = [];

    #[Url(as: 'tags', except: '')]
    public array $selectedTags = [];

    #[Url(as: 'assigned', except: '')]
    public array $selectedAssignees = [];

    #[Url(as: 'created', except: '')]
    public array $selectedCreators = [];

    public function mount()
    {
        $this->team = Auth::user()->currentTeam;
    }

    #[Computed]
    public function projects()
    {
        return $this->team->projects()->orderBy('name')->get();
    }

    #[Computed]
    public function tags()
    {
        return $this->team->tags()->orderBy('name')->get();
    }

    #[Computed]
    public function teamMembers()
    {
        return $this->team->allMembers()->sortBy('name');
    }

    #[Computed]
    public function tasks()
    {
        $query = Task::with(['section.project', 'assignees', 'tags', 'user'])
            ->whereHas('section.project.team', function ($query) {
                $query->where('id', $this->team->id);
            });

        // Filter by projects
        if (! empty($this->selectedProjects)) {
            $query->whereHas('section.project', function ($query) {
                $query->whereIn('id', $this->selectedProjects);
            });
        }

        // Filter by tags
        if (! empty($this->selectedTags)) {
            $query->whereHas('tags', function ($query) {
                $query->whereIn('id', $this->selectedTags);
            });
        }

        // Filter by assignees
        if (! empty($this->selectedAssignees)) {
            $query->whereHas('assignees', function ($query) {
                $query->whereIn('users.id', $this->selectedAssignees);
            });
        }

        // Filter by creators
        if (! empty($this->selectedCreators)) {
            $query->whereIn('user_id', $this->selectedCreators);
        }

        return $query->orderBy('updated_at', 'desc')->get();
    }

    public function clearFilters()
    {
        $this->selectedProjects = [];
        $this->selectedTags = [];
        $this->selectedAssignees = [];
        $this->selectedCreators = [];
    }
};
?>

<div class="h-full">
    <flux:heading>All tasks</flux:heading>

    <flux:spacer class="my-4" />

    <div class="flex gap-2">
        <!-- Projects Filter -->
        <flux:pillbox multiple placeholder="Project..." wire:model.live="selectedProjects">
            @foreach ($this->projects as $project)
                <flux:pillbox.option value="{{ $project->id }}">{{ $project->name }}</flux:pillbox.option>
            @endforeach
        </flux:pillbox>

        <!-- Tags Filter -->
        <flux:pillbox multiple placeholder="Tagged..." wire:model.live="selectedTags">
            @foreach ($this->tags as $tag)
                <flux:pillbox.option value="{{ $tag->id }}">{{ $tag->name }}</flux:pillbox.option>
            @endforeach
        </flux:pillbox>

        <!-- Assignees Filter -->
        <flux:pillbox multiple placeholder="Assigned to..." wire:model.live="selectedAssignees">
            @foreach ($this->teamMembers as $member)
                <flux:pillbox.option value="{{ $member->id }}">{{ $member->name }}</flux:pillbox.option>
            @endforeach
        </flux:pillbox>

        <!-- Creators Filter -->
        <flux:pillbox multiple placeholder="Added by..." wire:model.live="selectedCreators">
            @foreach ($this->teamMembers as $member)
                <flux:pillbox.option value="{{ $member->id }}">{{ $member->name }}</flux:pillbox.option>
            @endforeach
        </flux:pillbox>

        <!-- Clear Filters Button -->
        @if (! empty($selectedProjects) || ! empty($selectedTags) || ! empty($selectedAssignees) || ! empty($selectedCreators))
            <flux:button variant="ghost" wire:click="clearFilters">Clear All</flux:button>
        @endif
    </div>

    <flux:spacer class="my-4" />

    <div class="grid gap-2 lg:grid-cols-3">
        @foreach ($this->tasks as $task)
            <livewire:projects.task :task="$task" :key="'task-' . $task->id" class="rounded-lg shadow-xs" />
        @endforeach
    </div>
</div>
