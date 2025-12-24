<?php

use App\Models\Team;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public Team $team;

    public function mount()
    {
        $this->authorize('view', $this->team);
    }

    #[Computed]
    public function projects()
    {
        return $this->team->projects()->get();
    }
};
?>

<div>
    <flux:heading level="1" size="lg">{{ $team->name }}</flux:heading>

    <flux:spacer class="my-6" />

    <flux:heading level="2" size="md">Projects</flux:heading>

    <flux:spacer class="my-4" />

    @if ($this->projects->isEmpty())
        <flux:callout variant="secondary" icon="folder">
            <flux:callout.heading>No projects yet</flux:callout.heading>
            <flux:text>Create your first project to get started.</flux:text>
        </flux:callout>
    @else
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
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
