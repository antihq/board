<?php

use App\Models\Project;
use App\Models\Task;
use Livewire\Attributes\Async;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Renderless;
use Livewire\Component;

new class extends Component
{
    public Project $project;

    #[Renderless, Async]
    public function moveTask($item, $position)
    {
        //
    }

    #[Computed]
    public function completedTasks()
    {
        return $this->project->tasks()->get();
    }
};
?>

@placeholder
    <flux:skeleton.group animate="shimmer">
        <flux:skeleton.line class="mb-2 w-1/4" />
        <flux:skeleton.line />
        <flux:skeleton.line />
        <flux:skeleton.line class="w-3/4" />
    </flux:skeleton.group>
@endplaceholder

<flux:kanban.column :$attributes>
    <flux:kanban.column.header heading="Done" count="{{ $this->completedTasks->count() }}" />
    <flux:kanban.column.cards wire:sort="moveTask" wire:sort:group="sections">
        @foreach ($this->completedTasks as $task)
            <livewire:projects.task :$task wire:key="{{ $task->id }}" wire:sort:item="{{ $task->id }}" />
        @endforeach
    </flux:kanban.column.cards>
</flux:kanban.column>
