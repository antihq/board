<?php

use App\Models\Project;
use App\Models\Team;
use Livewire\Attributes\Async;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;
use Livewire\Component;

new class extends Component
{
    public Team $team;

    public Project $project;

    public string $title = '';

    public function mount()
    {
        $this->authorize('view', $this->team);
    }

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

    #[On('section.deleted')]
    public function refreshSections()
    {
        unset($this->sections);
    }

    #[Renderless, Async]
    public function sortItem($item, $position)
    {
        // Validate position bounds - positions are 1-based
        $totalSections = $this->project->sections()->count();

        if ($position < 1 || $position > $totalSections) {
            return;
        }

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
            $this->project
                ->sections()
                ->where('id', '!=', $section->id)
                ->where('order', '>', $oldOrder)
                ->where('order', '<=', $position)
                ->decrement('order');
        } else {
            // Moving up: increment orders of sections in between
            $this->project
                ->sections()
                ->where('id', '!=', $section->id)
                ->where('order', '>=', $position)
                ->where('order', '<', $oldOrder)
                ->increment('order');
        }
    }
};
?>

<div class="h-full">
    <div class="flex items-center justify-between">
        <flux:heading level="1" size="lg">{{ $project->name }}</flux:heading>

        <flux:button size="sm" href="{{ route('projects.settings.general', [$team, $project]) }}" wire:navigate>
            Settings
        </flux:button>
    </div>

    <flux:spacer class="my-4" />

    <div class="relative h-full overflow-hidden">
        <div
            class="pointer-events-none absolute inset-y-0 right-0 h-full w-48 bg-gradient-to-l from-white to-transparent dark:from-zinc-900 dark:to-transparent"
        ></div>
        <div class="h-full w-full overflow-x-auto">
            <flux:kanban wire:sort="sortItem">
                <livewire:columns.pending :project="$project" />
                @foreach ($this->sections as $section)
                    <livewire:columns.section
                        :section="$section"
                        wire:key="{{ $section->id }}"
                        wire:sort:item="{{ $section->id }}"
                    />
                @endforeach

                <livewire:columns.completed :project="$project" />
                <livewire:columns.closed :project="$project" />
                <flux:kanban.column wire:sort:ignore>
                    <flux:kanban.column.footer class="pt-2">
                        <form wire:submit.prevent="createSection">
                            <flux:composer
                                wire:model="title"
                                rows="1"
                                label="Section title"
                                label:sr-only
                                placeholder="Enter section title..."
                                submit="enter"
                            >
                                <x-slot name="actionsLeading">
                                    <flux:button type="submit" size="sm" variant="primary">Add section</flux:button>
                                </x-slot>
                            </flux:composer>
                        </form>
                    </flux:kanban.column.footer>
                </flux:kanban.column>
                <div class="h-full w-48 shrink-0">&nbsp;</div>
            </flux:kanban>
        </div>
    </div>
</div>
