<?php

use App\Models\Project;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Async;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;
use Livewire\Component;

new class extends Component
{
    public Project $project;

    public int $page = 1;

    #[On('task.moved')]
    #[On('task.updated')]
    #[On('task-deleted')]
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

    #[Computed]
    public function colorClass()
    {
        return 'bg-green-50 dark:bg-green-950';
    }

    #[Renderless, Async]
    public function sortItem($item, $_position)
    {
        $task = $this->project->tasks()->findOrFail($item);

        if ($task->isCompleted()) {
            Flux::toast(heading: 'Cannot move', text: 'Card is already in this column', variant: 'warning');

            return;
        }

        $task->complete(Auth::user());

        $this->dispatch('task.moved');
    }

    public function deleteTask($taskId)
    {
        $task = $this->project->tasks()->findOrFail($taskId);

        $this->authorize('delete', $task->project);

        $task->delete();

        $this->dispatch('task-deleted', taskId: $task->id);
    }
};
?>

@placeholder
    <div>
        <flux:skeleton.group
            animate="shimmer"
            class="w-80 max-w-80 space-y-3 rounded-lg p-2 [:where(&)]:bg-zinc-100 dark:[:where(&)]:bg-zinc-800"
        >
            <flux:skeleton.line class="w-1/2" />

            @foreach (range(1, random_int(5, 10)) as $item)
                <flux:skeleton class="size-20 w-full" />
            @endforeach
        </flux:skeleton.group>
    </div>
@endplaceholder

<flux:kanban.column {{ $attributes->class($this->colorClass) }}>
    <flux:kanban.column.header heading="Completed" />
    <flux:kanban.column.cards>
        <div class="flex flex-col gap-2" wire:sort="sortItem" wire:sort:group="tasks">
            @forelse ($this->tasks as $task)
                <livewire:columns.task-card
                    :$task
                    wire:sort:item="{{ $task->id }}"
                    wire:key="task-{{ $task->id }}"
                    lazy:bundle
                    lazy
                />
            @empty
                <flux:text class="py-3 text-center">Drag and drop tasks here</flux:text>
            @endforelse
        </div>
    </flux:kanban.column.cards>
    @if ($this->hasMore)
        <div wire:intersect.margin.200px="loadMore" wire:island="completed-tasks">
            <flux:text class="pb-2 text-center">Loading...</flux:text>
        </div>
    @endif
</flux:kanban.column>
