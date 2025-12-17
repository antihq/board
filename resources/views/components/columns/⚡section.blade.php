<?php

use App\Models\Section;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public Section $section;

    #[Computed]
    public function tasks()
    {
        return $this->section->tasks()->latest('section_moved_at')->get();
    }

    public function sortItem($item, $_position)
    {
        $task = $this->section->project->tasks()->findOrFail($item);

        $updateData = [
            'section_id' => $this->section->id,
            'section_moved_at' => now(),
            'section_moved_by' => Auth::id(),
        ];

        if ($task->completed_at !== null) {
            $updateData = array_merge($updateData, [
                'completed_at' => null,
                'completed_by' => null,
                'reopened_at' => now(),
                'reopened_by' => Auth::id(),
            ]);
        }

        $task->update($updateData);
    }
};
?>

<flux:kanban.column>
    <flux:kanban.column.header :heading="$section->title" count="{{ $this->tasks->count() }}" />
    <flux:kanban.column.cards wire:sort="sortItem" wire:sort:group="cards">
        @foreach ($this->tasks as $task)
            <flux:kanban.card heading="{{ $task->title }}" wire:sort:item="{{ $task->id }}" />
        @endforeach
    </flux:kanban.column.cards>
</flux:kanban.column>
