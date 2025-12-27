<?php

use App\Models\Project;
use App\Models\Team;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public Team $team;

    public string $name = '';

    public function mount()
    {
        $this->authorize('view', $this->team);
    }

    public function create()
    {
        $this->validate([
            'name' => 'required',
        ]);

        $name = $this->pull('name');

        $project = $this->team->projects()->create([
            'name' => $name,
            'handle' => Project::generateUniqueHandle($name),
        ]);

        $this->redirect(route('projects.show', [$this->team, $project]), navigate: true);
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

            <flux:modal name="add-project" class="md:w-96">
                <x-slot name="trigger">
                    <flux:button size="sm" variant="primary" icon="plus">Add project</flux:button>
                </x-slot>
                <form wire:submit="create">
                    <div class="space-y-6">
                        <div>
                            <flux:heading size="lg">New project</flux:heading>
                            <flux:text class="mt-2">Create a new project for your team.</flux:text>
                        </div>
                        <flux:input
                            wire:model="name"
                            label="Project name"
                            placeholder="Enter project name"
                            autofocus
                            required
                        />
                        <div class="flex">
                            <flux:spacer />
                            <flux:button type="submit" variant="primary" size="sm" color="green">
                                Create project
                            </flux:button>
                        </div>
                    </div>
                </form>
            </flux:modal>
        </div>

        @if ($this->projects->isEmpty())
            <flux:callout variant="secondary" icon="folder">
                <flux:callout.heading>No projects yet</flux:callout.heading>
                <flux:text>Create your first project to get started.</flux:text>
            </flux:callout>
        @else
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-5 md:gap-6">
                @foreach ($this->projects as $project)
                    <a
                        href="{{ route('projects.show', [$team, $project]) }}"
                        wire:key="project-{{ $project->id }}"
                        wire:navigate
                    >
                        <flux:kanban.card :heading="$project->name" />
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
