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
            ->tasks()
            ->closed()
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
            ->closed()
            ->count();

        return $total > $this->page * 20;
    }

    public function sortItem($item, $_position)
    {
        $task = $this->project->tasks()->findOrFail($item);

        $updateData = [];

        if ($task->closed_at === null) {
            $updateData = [
                'closed_at' => now(),
                'closed_by' => Auth::id(),
            ];

            if ($task->completed_at === null) {
                $updateData['completed_at'] = now();
                $updateData['completed_by'] = Auth::id();
            }

            $updateData['reopened_at'] = null;
            $updateData['reopened_by'] = null;
        }

        if (! empty($updateData)) {
            $task->update($updateData);
            $this->dispatch('task.moved');
        }
    }
};
?>

<flux:kanban.column {{ $attributes }}>
    <flux:kanban.column.header heading="Closed" count="{{ $this->tasks->count() }}" />
    <flux:kanban.column.cards>
        @island(name: 'closed-tasks')
            <div
                x-data="{ isDragging: false, refreshInterval: null }"
                x-init="
                    if (! refreshInterval)
                        refreshInterval = setInterval(() => {
                            if (! isDragging) $wire.$refresh()
                        }, 2500)
                "
                @dragstart="isDragging = true"
                @dragend="isDragging = false"
                class="flex flex-col gap-2"
                wire:sort="sortItem"
                wire:sort:group="tasks"
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
                                            @if ($task->closer)
                                                <flux:avatar
                                                    circle
                                                    size="xs"
                                                    :src="$task->closer->profilePhotoUrl()"
                                                    name="{{ $task->closer->name }}"
                                                    color="auto"
                                                    color:seed="{{ $task->closer->id }}"
                                                    tooltip="{{ $task->closer->name }}"
                                                />
                                            @endif

                                            <flux:text class="text-xs">
                                                closed
                                                {{ $task->closed_at->diffForHumans() }}
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
                wire:island="closed-tasks"
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
