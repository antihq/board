<?php

use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
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

        DB::transaction(function () {
            $maxNumber =
                $this->project
                    ->tasks()
                    ->lockForUpdate()
                    ->max('number') ?? 0;

            $this->project->tasks()->create([
                'team_id' => $this->project->team_id,
                'user_id' => Auth::id(),
                'title' => $this->pull('title'),
                'number' => $maxNumber + 1,
            ]);
        });
    }

    #[Computed]
    public function tasks()
    {
        return $this->project
            ->tasks()
            ->pending()
            ->orderBy('prioritized_at', 'desc')
            ->orderBy('updated_at', 'desc')
            ->get();
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

        if ($task->section_id !== null) {
            $updateData = array_merge($updateData, [
                'section_id' => null,
                'section_moved_at' => null,
                'section_moved_by' => null,
            ]);
        }

        if (! empty($updateData)) {
            $task->update($updateData);
        }
    }
};
?>

<flux:kanban.column {{ $attributes }}>
    <flux:kanban.column.header heading="Pending" count="{{ $this->tasks->count() }}" />
    <flux:kanban.column.cards wire:sort="sortItem" wire:sort:group="tasks">
        @foreach ($this->tasks as $task)
            <flux:modal class="w-full max-w-[95vw] lg:max-w-150">
                <x-slot name="trigger">
                    <flux:kanban.card as="button" heading="{{ $task->title }}" wire:sort:item="{{ $task->id }}" />
                </x-slot>

                <livewire:task :task="$task" wire:key="task-{{ $task->id }}" lazy />
            </flux:modal>
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
                submit="enter"
            >
                <x-slot name="actionsLeading">
                    <flux:button type="submit" size="sm" variant="primary" color="green" wire:click="createTask">
                        Add task
                    </flux:button>
                </x-slot>
            </flux:composer>
        </form>
    </flux:kanban.column.footer>
</flux:kanban.column>
