<?php

use App\Models\Section;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public Section $section;

    #[Computed]
    public function tasks()
    {
        return $this->section
            ->tasks()
            ->orderBy('prioritized_at', 'desc')
            ->orderBy('updated_at', 'desc')
            ->get();
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

<flux:kanban.column {{ $attributes }}>
    <flux:kanban.column.header :heading="$section->title" count="{{ $this->tasks->count() }}" />
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
</flux:kanban.column>
