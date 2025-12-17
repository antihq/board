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
        return $this->project->tasks()->done()->latest('completed_at')->get();
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

        if ($task->section_id !== null) {
            $updateData = array_merge($updateData, [
                'section_id' => null,
                'section_moved_at' => null,
                'section_moved_by' => null,
            ]);
        }

        if (!empty($updateData)) {
            $task->update($updateData);
        }
    }
};
?>

<flux:kanban.column>
    <flux:kanban.column.header heading="Done" count="{{ $this->tasks->count() }}" />
    <flux:kanban.column.cards wire:sort="sortItem" wire:sort:group="tasks">
        @foreach ($this->tasks as $task)
            <livewire:task-card :task="$task" wire:key="task-{{ $task->id }}" />
        @endforeach
    </flux:kanban.column.cards>
</flux:kanban.column>
