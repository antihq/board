<?php

use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public Project $project;

    public string $title = '';

    public function createTask()
    {
        $this->validate([
            'title' => 'required',
        ]);

        $this->project->tasks()->create([
            'team_id' => $this->project->team_id,
            'user_id' => Auth::id(),
            'title' => $this->pull('title'),
        ]);
    }
};
?>

<flux:kanban.column>
    <flux:kanban.column.header heading="Inbox" count="{{ $project->tasks->count() }}" />
    <flux:kanban.column.cards>
        @foreach ($project->tasks as $task)
            <flux:kanban.card heading="{{ $task->title }}" />
        @endforeach
    </flux:kanban.column.cards>
    <flux:kanban.column.footer>
        <form wire:submit.prevent="createTask">
            <flux:composer
                wire:model="title"
                rows="1"
                label="Task title"
                label:sr-only
                placeholder="Enter task title..."
            >
                <x-slot name="actionsLeading">
                    <flux:button type="submit" size="sm" variant="primary" color="green" wire:click="createTask">
                        Add task
                    </flux:button>
                    <flux:button size="sm" variant="subtle">Cancel</flux:button>
                </x-slot>
            </flux:composer>
        </form>
    </flux:kanban.column.footer>
</flux:kanban.column>
