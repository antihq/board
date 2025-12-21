<?php

use App\Models\Project;
use App\Models\Team;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    public Team $team;

    public Project $project;

    #[Validate('required|string|max:255')]
    public $name;

    #[Validate('required|string|max:255|unique:projects,handle')]
    public $handle;

    public function mount(Team $team, Project $project)
    {
        $this->authorize('view', $team);
        $this->authorize('update', $project);

        $this->team = $team;
        $this->project = $project;
        $this->name = $project->name;
        $this->handle = $project->handle;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'handle' => 'required|string|max:255|unique:projects,handle,' . $this->project->id,
        ]);

        $this->project->update([
            'name' => $this->name,
            'handle' => $this->handle,
        ]);

        return redirect()->route('projects.show', [$this->team, $this->project]);
    }

    public function cancel()
    {
        return redirect()->route('projects.show', [$this->team, $this->project]);
    }
};
?>

<div class="h-full overflow-auto">
    <flux:heading level="1" size="lg">Edit Project</flux:heading>

    <flux:spacer class="my-6" />

    <flux:container>
        <form wire:submit.prevent="save" class="space-y-6">
            <flux:field>
                <flux:label>Project Name</flux:label>
                <flux:input wire:model="name" placeholder="Enter project name..." value="{{ $name }}" required />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Project Handle</flux:label>
                <flux:input
                    wire:model="handle"
                    placeholder="project-handle"
                    value="{{ $handle }}"
                    description:trailing="This will be used in URLs and must be unique across all projects."
                    required
                />
                <flux:error name="handle" />
            </flux:field>

            <flux:separator />

            <flux:button type="submit" variant="primary" color="green">Save Changes</flux:button>

            <flux:button wire:click="cancel" variant="ghost">Cancel</flux:button>
        </form>
    </flux:container>
</div>
