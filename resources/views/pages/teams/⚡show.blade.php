<?php

use App\Models\Team;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public Team $team;

    public function mount()
    {
        $this->authorize('view', $this->team);
    }

    #[Computed]
    public function projects()
    {
        return $this->team
            ->projects()
            ->latest()
            ->take(5)
            ->get();
    }
};
?>

<div>
    <flux:heading level="1" class="text-lg!">{{ $team->name }}</flux:heading>

    <flux:spacer class="my-6" />

    <div class="space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <flux:heading level="2" size="lg">Recent projects</flux:heading>

            <flux:button size="sm" variant="primary" icon="plus">Add project</flux:button>
        </div>

        @if ($this->projects->isEmpty())
            <flux:callout variant="secondary" icon="folder">
                <flux:callout.heading>No projects yet</flux:callout.heading>
                <flux:text>Create your first project to get started.</flux:text>
            </flux:callout>
        @else
            <div class="grid grid-cols-1 gap-6 md:grid-cols-5">
                @foreach ($this->projects as $project)
                    @if ($project->handle)
                        <a href="{{ route('projects.show', [$team, $project]) }}" wire:navigate>
                            <flux:kanban.card :heading="$project->name" />
                        </a>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</div>
