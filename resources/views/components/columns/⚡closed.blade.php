<?php

use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public Project $project;

    #[On('task.moved')]
    public function refreshTasks()
    {
        unset($this->tasks);
    }

    #[Computed]
    public function tasks()
    {
        return $this->project
            ->tasks()
            ->closed()
            ->with(['creator', 'project', 'tags', 'checklistItems', 'assignees'])
            ->orderBy('prioritized_at', 'desc')
            ->orderBy('updated_at', 'desc')
            ->get();
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
    <flux:kanban.column.cards wire:sort="sortItem" wire:sort:group="tasks">
        @foreach ($this->tasks as $task)
            <flux:modal class="w-full max-w-[95vw] lg:max-w-150" wire:key="task-{{ $task->id }}">
                <x-slot name="trigger">
                    <flux:kanban.card as="button" heading="{{ $task->title }}" wire:sort:item="{{ $task->id }}">
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

                                @unless ($task->checklistItems->isEmpty())
                                    <flux:text class="text-xs">
                                        {{ $task->checklistItems->where('completed', true)->count() }}/{{ $task->checklistItems->count() }}
                                    </flux:text>
                                @endunless
                            </div>
                        </x-slot>
                        <x-slot name="footer">
                            <div class="flex items-center gap-3">
                                <flux:text
                                    class="text-xs"
                                    tooltip="{{ $task->creator->name }} · {{ $task->created_at->isToday() ? 'Today' : $task->created_at->diffForHumans() }}"
                                >
                                    {{ $task->creator->initials() }} ·
                                    @if ($task->created_at->isToday())
                                        Today
                                    @else
                                        {{ $task->created_at->diffForHumans() }}
                                    @endif
                                </flux:text>
                                <flux:text class="text-xs">
                                    @if ($task->updated_at->isToday())
                                        Today
                                    @else
                                        {{ $task->updated_at->diffForHumans() }}
                                    @endif
                                </flux:text>
                                <flux:avatar.group>
                                    @foreach ($task->assignees->take(3) as $assignee)
                                        <flux:avatar
                                            circle
                                            size="xs"
                                            name="{{ $assignee->name }}"
                                            color="auto"
                                            color:seed="{{ $assignee->id }}"
                                            tooltip="{{ $assignee->name }}"
                                        />
                                    @endforeach

                                    @if ($task->assignees->count() > 3)
                                        <flux:avatar circle size="xs">{{ $task->assignees->count() }}+</flux:avatar>
                                    @endif
                                </flux:avatar.group>
                            </div>
                        </x-slot>
                    </flux:kanban.card>
                </x-slot>

                <livewire:task :task="$task" wire:key="task-{{ $task->id }}" lazy />
            </flux:modal>
        @endforeach
    </flux:kanban.column.cards>
</flux:kanban.column>
