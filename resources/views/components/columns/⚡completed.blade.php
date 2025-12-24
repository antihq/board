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
            ->completed()
            ->with(['creator', 'project', 'tags', 'comments', 'assignees'])
            ->orderBy('prioritized_at', 'desc')
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    public function sortItem($item, $_position)
    {
        $task = $this->project->tasks()->findOrFail($item);

        $updateData = [];

        if ($task->completed_at === null) {
            $updateData = [
                'completed_at' => now(),
                'completed_by' => Auth::id(),
                'reopened_at' => null,
                'reopened_by' => null,
            ];
        }

        if ($task->closed_at !== null) {
            $updateData = array_merge($updateData, [
                'closed_at' => null,
                'closed_by' => null,
            ]);
        }

        if (! empty($updateData)) {
            $task->update($updateData);
            $this->dispatch('task.moved');
        }
    }
};
?>

<flux:kanban.column {{ $attributes }}>
    <flux:kanban.column.header heading="Completed" count="{{ $this->tasks->count() }}" />
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
                            </div>
                        </x-slot>
                        <x-slot name="footer">
                            <div class="flex w-full items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <flux:avatar
                                        circle
                                        size="xs"
                                        name="{{ $task->completer->name }}"
                                        color="auto"
                                        color:seed="{{ $task->completer->id }}"
                                        tooltip="{{ $task->completer->name }}"
                                    />
                                    <flux:text class="text-xs">
                                        completed
                                        {{ $task->completed_at->diffForHumans() }}
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
                </x-slot>

                <livewire:task :task="$task" wire:key="task-{{ $task->id }}" lazy />
            </flux:modal>
        @endforeach
    </flux:kanban.column.cards>
</flux:kanban.column>
