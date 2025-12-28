<?php

use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public Project $project;

    public int $page = 1;

    #[On('task.moved')]
    #[On('task.updated')]
    public function refreshTasks()
    {
        unset($this->tasks);
    }

    public function loadMore()
    {
        $this->page++;
    }

    #[Computed]
    public function tasks()
    {
        return $this->project
            ->completedTasks()
            ->with(['creator', 'project', 'tags', 'comments', 'assignees'])
            ->take($this->page * 25)
            ->get();
    }

    #[Computed]
    public function hasMore()
    {
        $total = $this->project->completedTasks()->count();

        return $total > $this->page * 25;
    }

    public function sortItem($item, $_position)
    {
        $task = $this->project->tasks()->findOrFail($item);

        $task->moveToCompleted(Auth::user());

        $this->dispatch('task.moved');
    }
};
?>

<flux:kanban.column {{ $attributes }}>
    <flux:kanban.column.header heading="Completed" count="{{ $this->tasks->count() }}" />
    <flux:kanban.column.cards>
        @island(name: 'completed-tasks', always: true)
            <div class="flex flex-col gap-2" wire:sort="sortItem" wire:sort:group="tasks">
                @forelse ($this->tasks as $task)
                    <div wire:sort:item="{{ $task->id }}" wire:key="task-{{ $task->id }}">
                        <flux:modal.trigger :name="'task-' . $task->id">
                            <x-columns.task-card :task="$task" />
                        </flux:modal.trigger>

                        <flux:modal :name="'task-' . $task->id" class="w-full max-w-[95vw] lg:max-w-150">
                            <livewire:task :task="$task" lazy />
                        </flux:modal>
                    </div>
                @empty
                    <flux:text class="py-3 text-center">Drag and drop tasks here</flux:text>
                @endforelse
            </div>
        @endisland
    </flux:kanban.column.cards>
    @if ($this->hasMore)
        <div wire:intersect.margin.200px="loadMore" wire:island="completed-tasks">
            <flux:text class="pb-2 text-center">Loading...</flux:text>
        </div>
    @endif
</flux:kanban.column>
