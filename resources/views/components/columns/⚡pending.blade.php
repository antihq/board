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

<flux:kanban.column {{ $attributes }}>
    <flux:kanban.column.header heading="Pending">
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
