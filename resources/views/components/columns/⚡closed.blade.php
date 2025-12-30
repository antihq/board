<?php

use App\Models\Project;
use Flux\Flux;
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
            ->closedTasks()
            ->with(['creator', 'project', 'tags', 'comments', 'assignees'])
            ->take($this->page * 25)
            ->get();
    }

    #[Computed]
    public function hasMore()
    {
        $total = $this->project->closedTasks()->count();

        return $total > $this->page * 25;
    }

    public function sortItem($item, $_position)
    {
        $task = $this->project->tasks()->findOrFail($item);

        if ($task->isClosed()) {
            Flux::toast(heading: 'Cannot move', text: 'Card is already in this column', variant: 'warning');

            return;
        }

        $task->close(Auth::user());

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

<flux:kanban.column {{ $attributes }}>
    <flux:kanban.column.header heading="Closed" count="{{ $this->tasks->count() }}" />
    <flux:kanban.column.cards>
        @island(name: 'closed-tasks', always: true)
            <div class="flex flex-col gap-2" wire:sort="sortItem" wire:sort:group="tasks">
                @forelse ($this->tasks as $task)
                    <div wire:sort:item="{{ $task->id }}" wire:key="task-{{ $task->id }}">
                        <flux:modal.trigger :name="'task-' . $task->id">
                            <x-columns.task-card :task="$task" />
                        </flux:modal.trigger>

                        <flux:modal
                            :name="'task-' . $task->id"
                            @close="$refresh"
                            class="w-full max-w-[95vw] lg:max-w-216"
                        >
                            <livewire:task :task="$task" lazy />
                        </flux:modal>

                        <flux:modal :name="'delete-task-' . $task->id" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete task?</flux:heading>
                                    <flux:text class="mt-2">
                                        You're about to delete this task. This action cannot be reversed.
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer />
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button
                                        type="submit"
                                        variant="danger"
                                        wire:click="deleteTask({{ $task->id }})"
                                    >
                                        Delete task
                                    </flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </div>
                @empty
                    <flux:text class="py-3 text-center">Drag and drop tasks here</flux:text>
                @endforelse
            </div>
        @endisland
    </flux:kanban.column.cards>
    @if ($this->hasMore)
        <div wire:intersect.margin.200px="loadMore" wire:island="closed-tasks">
            <flux:text class="pb-2 text-center">Loading...</flux:text>
        </div>
    @endif
</flux:kanban.column>
