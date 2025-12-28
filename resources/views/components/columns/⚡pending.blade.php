<?php

use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public Project $project;

    public string $title = '';

    public int $page = 1;

    public bool $showForm = false;

    #[On('task.moved')]
    #[On('task.updated')]
    #[On('section.deleted')]
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

    public function sortItem($item, $_position)
    {
        $task = $this->project->tasks()->findOrFail($item);

        $task->moveToPending(Auth::user());

        $this->dispatch('task.moved');
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
                    <flux:button type="submit" size="sm" variant="primary" color="green" wire:click="createTask">
                        Add task
                    </flux:button>
                    <flux:button size="sm" color="green" wire:click="$js.hideForm">Cancel</flux:button>
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
                            <flux:kanban.card as="button" heading="{{ $task->title }}">
                                <x-slot name="header">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        @if ($task->project)
                                            <flux:text class="font-mono text-xs">
                                                {{ $task->project->handle }}-{{ $task->number }}
                                            </flux:text>
                                        @endif

                                        @unless ($task->tags->isEmpty())
                                            <div class="flex gap-1">
                                                @foreach ($task->tags->take(3) as $tag)
                                                    <flux:badge size="sm">{{ $tag->name }}</flux:badge>
                                                @endforeach

                                                @if ($task->tags->count() > 3)
                                                    <flux:badge size="sm">+{{ $task->tags->count() - 3 }}</flux:badge>
                                                @endif
                                            </div>
                                        @endunless
                                    </div>
                                </x-slot>
                                <x-slot name="footer">
                                    <div class="flex w-full items-center justify-between gap-3">
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
                            </flux:kanban.card>
                        </flux:modal.trigger>

                        <flux:modal :name="'task-' . $task->id" class="w-full max-w-[95vw] lg:max-w-150">
                            <livewire:task :task="$task" lazy />
                        </flux:modal>
                    </div>
                @endforeach
            </div>
        @endisland
    </flux:kanban.column.cards>
    @if ($this->hasMore)
        <flux:kanban.column.footer>
            <flux:button
                wire:click="loadMore"
                wire:island="tasks"
                type="button"
                size="sm"
                variant="ghost"
                align="start"
            >
                Load more
            </flux:button>
        </flux:kanban.column.footer>
    @endif
</flux:kanban.column>

<script>
    this.$js.showForm = () => {
        this.showForm = true;
        setTimeout(() => {
            const textarea = this.$refs.input?.querySelector('textarea');
            if (textarea) {
                textarea.focus();
            }
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
