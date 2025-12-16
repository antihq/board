<?php

use App\Models\Task;
use App\Models\Column;
use Livewire\Attributes\Async;
use Livewire\Attributes\Renderless;
use Livewire\Component;

new class extends Component
{
    public Column $column;

    #[Renderless, Async]
    public function moveTask($item, $position)
    {
        //
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
    <flux:kanban.column.header :heading="$column->name" :count="$this->column->tasks->count()" />
    <flux:kanban.column.cards wire:sort="moveTask" wire:sort:group="columns">
        @foreach ($this->column->tasks as $task)
            <livewire:projects.task :$task wire:key="{{ $task->id }}" wire:sort:item="{{ $task->id }}" />
        @endforeach
    </flux:kanban.column.cards>
</flux:kanban.column>
