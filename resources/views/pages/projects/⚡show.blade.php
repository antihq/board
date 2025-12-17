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
            'order' => ($this->project->sections()->max('order') ?? 0) + 1,
        ]);
    }

    #[Computed]
    public function sections()
    {
        return $this->project->sections()->get();
    }

    public function sortItem($item, $position)
    {
        $section = $this->project->sections()->findOrFail($item);
        $oldOrder = $section->order;

        // If the section is already at this position, do nothing
        if ($oldOrder == $position) {
            return;
        }

        // Update the section's order
        $section->update(['order' => $position]);

        // Update orders of other sections
        if ($oldOrder < $position) {
            // Moving down: decrement orders of sections in between
            $this->project->sections()
                ->where('id', '!=', $section->id)
                ->where('order', '>', $oldOrder)
                ->where('order', '<=', $position)
                ->decrement('order');
        } else {
            // Moving up: increment orders of sections in between
            $this->project->sections()
                ->where('id', '!=', $section->id)
                ->where('order', '>=', $position)
                ->where('order', '<', $oldOrder)
                ->increment('order');
        }
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
            <flux:kanban wire:sort="sortItem">
                <livewire:columns.inbox :project="$project" wire:sort:ignore />
                @foreach ($this->sections as $section)
                    <livewire:columns.section :section="$section" wire:key="{{ $section->id }}" wire:sort:item="{{ $section->id }}" />
                @endforeach
                <livewire:columns.done :project="$project" wire:sort:ignore />
                <flux:kanban.column wire:sort:ignore>
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
