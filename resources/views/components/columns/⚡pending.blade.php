<?php

use App\Models\Project;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Async;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;
use Livewire\Component;

new class extends Component {
    public Project $project;

    public string $title = '';

    public int $page = 1;

    public bool $showForm = false;

    #[On('task.moved')]
    #[On('task.updated')]
    #[On('section.deleted')]
    #[On('task-deleted')]
    public function refreshTasks()
    {
        unset($this->tasks);
    }

    public function createTask()
    {
        $this->validate([
            'title' => 'required',
        ]);

        $this->project->addTask($this->pull('title'), Auth::user());

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
            ->pendingTasks()
            ->with(['creator', 'project', 'tags', 'comments', 'assignees'])
            ->take($this->page * 25)
            ->get();
    }

    #[Computed]
    public function hasMore()
    {
        return $this->project->pendingTasks()->count() > $this->page * 25;
    }

    #[Renderless, Async]
    public function sortItem($item, $_position)
    {
        $task = $this->project->tasks()->findOrFail($item);

        if ($task->isPending()) {
            Flux::toast(heading: 'Cannot move', text: 'Card is already in this column', variant: 'warning');

            return;
        }

        $task->moveToPending(Auth::user());

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
    <flux:kanban.column.header heading="Pending" count="{{ $this->tasks->count() }}">
        <x-slot name="actions">
            <flux:button variant="subtle" icon="plus" size="sm" wire:click="$js.showForm" />
        </x-slot>
    </flux:kanban.column.header>
    <div class="flex flex-col px-2 pb-2" wire:show="showForm" wire:cloak>
        <form wire:submit.prevent="createTask">
            <flux:composer
                wire:model="title"
                rows="1"
                label="Task title"
                label:sr-only
                placeholder="Enter task title..."
                submit="enter"
                wire:ref="input"
            >
                <x-slot name="actionsLeading">
                    <flux:button type="submit" size="sm" variant="primary" wire:click="createTask">
                        Add task
                    </flux:button>
                    <flux:button size="sm" wire:click="$js.hideForm">Cancel</flux:button>
                </x-slot>
            </flux:composer>
        </form>
    </div>
    <flux:kanban.column.cards>
        @island(name: 'tasks', always: true)
            <div class="flex flex-col gap-2" wire:sort="sortItem" wire:sort:group="tasks">
                @foreach ($this->tasks as $task)
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
                @endforeach
            </div>
        @endisland
    </flux:kanban.column.cards>
    @if ($this->hasMore)
        <div wire:intersect.margin.200px="loadMore" wire:island="tasks">
            <flux:text class="pb-2 text-center">Loading...</flux:text>
        </div>
    @endif
</flux:kanban.column>

<script>
    this.$js.showForm = () => {
        this.showForm = true;
        requestAnimationFrame(() => {
            setTimeout(() => {
                const textarea = this.$refs.input?.querySelector('textarea');
                if (textarea) {
                    textarea.focus();
                }
            }, 100);
        });
    };

    this.$js.hideForm = () => {
        this.showForm = false;
    };

    this.$el.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            if (this.showForm) {
                this.showForm = false;
            }
        }
    });
</script>
