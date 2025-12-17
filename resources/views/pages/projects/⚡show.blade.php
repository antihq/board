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

<div class="h-full">
    <flux:heading level="1">{{ $project->name }}</flux:heading>

    <flux:spacer class="my-4" />

    <div class="relative h-full">
        <div
            class="absolute inset-y-0 right-0 h-full w-48 bg-gradient-to-l from-white to-transparent dark:from-zinc-900 dark:to-transparent"
        ></div>
        <div class="h-full w-full overflow-x-auto">
            <flux:kanban>
                <livewire:columns.inbox :project="$project" />
                @foreach ($this->sections as $section)
                    <livewire:columns.section :section="$section" />
                @endforeach
                <livewire:columns.done :project="$project" />
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
                                </x-slot>
                            </flux:composer>
                        </form>
                    </flux:kanban.column.footer>
                </flux:kanban.column>
            </flux:kanban>
        </div>
    </div>
</div>
