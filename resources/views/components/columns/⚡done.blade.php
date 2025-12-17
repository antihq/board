<?php

use App\Models\Project;
use App\Models\Task;
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

    public function sortItem($item, $position)
    {
        $task = $this->project->tasks()->findOrFail($item);

        if ($task->completed_at === null) {
            $task->update([
                'completed_at' => now(),
                'completed_by' => Auth::id(),
                'reopened_at' => null,
                'reopened_by' => null,
            ]);
        }
    }
};
?>

<flux:kanban.column>
    <flux:kanban.column.header heading="Done" count="{{ $this->tasks->count() }}" />
    <flux:kanban.column.cards wire:sort="sortItem" wire:sort:group="cards">
        @foreach ($this->tasks as $task)
            <flux:kanban.card heading="{{ $task->title }}" wire:sort:item="{{ $task->id }}" />
        @endforeach
    </flux:kanban.column.cards>
</flux:kanban.column>
