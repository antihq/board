<?php

use App\Models\Project;
use App\Models\Team;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public Team $team;

    public Project $project;

    public string $title = '';

    public function createSection()
    {
        $this->validate([
            'title' => 'required',
        ]);

        $this->project->sections()->create([
            'title' => $this->pull('title'),
        ]);
    }

    #[Computed]
    public function sections()
    {
        return $this->project->sections()->latest()->get();
    }
};
?>

<div>
    <flux:heading level="1">{{ $project->name }}</flux:heading>

    <flux:spacer class="my-4" />

    <flux:kanban>
        <livewire:columns.inbox :project="$project" />
        <livewire:columns.done :project="$project" />
        @foreach ($this->sections as $section)
            <livewire:columns.section :section="$section" />
        @endforeach
        <flux:kanban.column>
            <flux:kanban.column.footer class="pt-2">
                <form wire:submit.prevent="createSection">
                    <flux:composer
                        wire:model="title"
                        rows="1"
                        label="Section title"
                        label:sr-only
                        placeholder="Enter section title..."
                    >
                        <x-slot name="actionsLeading">
                            <flux:button type="submit" size="sm" variant="primary" color="green">
                                Add section
                            </flux:button>
                            <flux:button size="sm" variant="subtle">Cancel</flux:button>
                        </x-slot>
                    </flux:composer>
                </form>
            </flux:kanban.column.footer>
        </flux:kanban.column>
    </flux:kanban>
</div>
