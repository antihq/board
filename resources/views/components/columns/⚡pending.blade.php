<?php

use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public Project $project;

    public string $title = '';

    public int $page = 1;

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

        DB::transaction(function () {
            $maxNumber =
                $this->project
                    ->tasks()
                    ->lockForUpdate()
                    ->max('number') ?? 0;

            $task = $this->project->tasks()->create([
                'team_id' => $this->project->team_id,
                'user_id' => Auth::id(),
                'title' => $this->pull('title'),
                'number' => $maxNumber + 1,
            ]);

            $task->subscribers()->attach(Auth::id());

            unset($this->tasks);
        });
    }

    public function loadMore()
    {
        $this->page++;
    }

    #[Computed]
    public function tasks()
    {
        return $this->project
            ->tasks()
            ->pending()
            ->with(['creator', 'project', 'tags', 'comments', 'assignees'])
            ->orderBy('prioritized_at', 'desc')
            ->orderBy('updated_at', 'desc')
            ->take($this->page * 20)
            ->get();
    }

    #[Computed]
    public function hasMore()
    {
        $total = $this->project
            ->tasks()
            ->pending()
            ->count();

        return $total > $this->page * 20;
    }

    public function sortItem($item, $_position)
    {
        $task = $this->project->tasks()->findOrFail($item);

        $updateData = [];

        if ($task->completed_at !== null) {
            $updateData = [
                'completed_at' => null,
                'completed_by' => null,
                'reopened_at' => now(),
                'reopened_by' => Auth::id(),
            ];
        }

        if ($task->closed_at !== null) {
            $updateData = array_merge($updateData, [
                'closed_at' => null,
                'closed_by' => null,
            ]);
        }

        if ($task->section_id !== null) {
            $updateData = array_merge($updateData, [
                'section_id' => null,
                'section_moved_at' => null,
                'section_moved_by' => null,
            ]);
        }

        if (! empty($updateData)) {
            $task->update($updateData);
            $this->dispatch('task.moved');
        }
    }
};
?>

<flux:kanban.column
    {{ $attributes }}
    x-data="{ showForm: false }"
    x-init="
        $watch(
            'showForm',
            (value) =>
                value &&
                $nextTick(() =>
                    $refs.titleInput.querySelector('input, textarea')?.focus(),
                ),
        )
    "
>
    <flux:kanban.column.header heading="Pending" count="{{ $this->tasks->count() }}">
        <x-slot name="actions">
            <flux:button variant="subtle" icon="plus" size="sm" @click="showForm = true" />
        </x-slot>
    </flux:kanban.column.header>
    <div class="flex flex-col px-2 pb-2" x-show="showForm" x-cloak @keydown.escape.window="showForm = false">
        <form wire:submit.prevent="createTask">
            <flux:composer
                wire:model="title"
                rows="1"
                label="Task title"
                label:sr-only
                placeholder="Enter task title..."
                submit="enter"
                x-ref="titleInput"
            >
                <x-slot name="actionsLeading">
                    <flux:button type="submit" size="sm" variant="primary" color="green" wire:click="createTask">
                        Add task
                    </flux:button>
                </x-slot>
            </flux:composer>
        </form>
    </div>
    <flux:kanban.column.cards>
        @island(name: 'tasks', always: true)
            <div
                @dragstart="$dispatch('kanban-drag-start'); $el.removeAttribute('wire:poll')"
                @dragend="$dispatch('kanban-drag-end'); $el.setAttribute('wire:poll', '')"
                @kanban-drag-start.window="$el.removeAttribute('wire:poll')"
                @kanban-drag-end.window="$el.setAttribute('wire:poll', '')"
                class="flex flex-col gap-2"
                wire:sort="sortItem"
                wire:sort:group="tasks"
                wire:poll
            >
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
    <flux:kanban.column.footer>
        @if ($this->hasMore)
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
        @endif
    </flux:kanban.column.footer>
</flux:kanban.column>
