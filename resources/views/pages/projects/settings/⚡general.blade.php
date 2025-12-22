<?php

use App\Models\Project;
use App\Models\Team;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    public Team $team;

    public Project $project;

    #[Validate('required|string|max:255')]
    public $name;

    #[Validate('required|string|max:255')]
    public $handle;

    public $editHandle = false;

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
            'handle' => [
                'required',
                'string',
                'max:255',
                Rule::unique('projects', 'handle')->ignore($this->project->id),
            ],
        ]);

        $originalHandle = $this->project->handle;

        $this->project->update([
            'name' => $this->name,
            'handle' => $this->handle,
        ]);

        $this->reset('editHandle');

        Flux::toast('Changes saved', variant: 'success');

        if ($originalHandle !== $this->handle) {
            return $this->redirectRoute('projects.settings.general', [$this->team, $this->project], navigate: true);
        }
    }
};
?>

<div class="space-y-6">
    <flux:heading level="1" size="lg">Project settings</flux:heading>

    <div class="space-y-8">
        <div class="border-b border-zinc-200 dark:border-zinc-700">
            <flux:navbar class="-mb-px">
                <flux:navbar.item
                    :href="route('projects.settings.general', [$team, $project])"
                    :accent="true"
                    wire:navigate
                >
                    General
                </flux:navbar.item>
                <flux:navbar.item
                    :href="route('projects.settings.auto-close', [$team, $project])"
                    :accent="false"
                    wire:navigate
                >
                    Auto-close
                </flux:navbar.item>
            </flux:navbar>
        </div>

        <div class="max-w-lg">
            <form wire:submit="save" class="space-y-6">
                <flux:input
                    wire:model="name"
                    type="text"
                    label="Project name"
                    placeholder="Enter project name"
                    description:trailing="This name will be visible to all team members."
                    required
                    maxlength="255"
                />

                <flux:input
                    wire:model="handle"
                    type="text"
                    label="Project handle"
                    placeholder="project-handle"
                    description:trailing="Unique identifier for your project used in urls."
                    :disabled="!$editHandle"
                    required
                    maxlength="255"
                >
                    <x-slot name="iconTrailing" wire:ignore>
                        @if ($editHandle)
                            <flux:button size="sm" variant="subtle" class="-mr-1" wire:click="$toggle('editHandle')">
                                Cancel
                            </flux:button>
                        @else
                            <flux:button size="sm" variant="subtle" class="-mr-1" wire:click="$toggle('editHandle')">
                                Edit
                            </flux:button>
                        @endif
                    </x-slot>
                </flux:input>

                <div class="flex gap-3">
                    <flux:button type="submit" variant="primary" color="green">Save</flux:button>
                </div>
            </form>
        </div>
    </div>
</div>
