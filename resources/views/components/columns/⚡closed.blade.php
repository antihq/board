<?php

use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public Project $project;

    #[Computed]
    public function tasks()
    {
        return $this->project
            ->tasks()
            ->closed()
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
                    <flux:kanban.card as="button" heading="{{ $task->title }}" wire:sort:item="{{ $task->id }}" />
                </x-slot>

                <livewire:task :task="$task" wire:key="task-{{ $task->id }}" lazy />
            </flux:modal>
        @endforeach
    </flux:kanban.column.cards>
</flux:kanban.column>
