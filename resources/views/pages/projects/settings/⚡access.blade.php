<?php

use App\Models\Project;
use App\Models\Team;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public Team $team;

    public Project $project;

    public bool $accessRestricted = false;

    public array $selectedMembers = [];

    public function mount(Team $team, Project $project)
    {
        $this->authorize('view', $team);
        $this->authorize('update', $project);

        $this->team = $team;
        $this->project = $project;
        $this->accessRestricted = (bool) $project->access_restricted;

        $this->selectedMembers = $project->members->pluck('id')->toArray();
    }

    public function save()
    {
        $members = $this->selectedMembers;

        if ($this->accessRestricted) {
            $members[] = $this->team->owner->id;
            $members = array_unique($members);
        }

        $this->project->members()->sync($members);

        Flux::toast('Project access updated', variant: 'success');
    }

    public function updatedAccessRestricted()
    {
        $this->project->update([
            'access_restricted' => $this->accessRestricted,
        ]);

        Flux::toast('Access restriction ' . ($this->accessRestricted ? 'enabled' : 'disabled'), variant: 'success');
    }

    #[Computed]
    public function teamMembers()
    {
        return $this->team->users->concat([$this->team->owner])->unique('id');
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
                    :accent="false"
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
                <flux:navbar.item
                    :href="route('projects.settings.access', [$team, $project])"
                    :accent="true"
                    wire:navigate
                >
                    Access
                </flux:navbar.item>
            </flux:navbar>
        </div>

        <div class="max-w-lg space-y-6">
            <div class="flex items-center gap-3">
                <flux:checkbox id="access_restricted" wire:model.live="accessRestricted" />
                <flux:label for="access_restricted">Restrict project access</flux:label>
            </div>
            <flux:text class="-mt-4 text-sm text-zinc-500 dark:text-zinc-400">
                When enabled, only selected team members can access this project.
            </flux:text>

            <div @if (! $accessRestricted) class="opacity-50 pointer-events-none" @endif>
                @if ($this->teamMembers->isEmpty())
                    <flux:callout variant="secondary">
                        <flux:callout.heading>No team members</flux:callout.heading>
                        <flux:text>Add team members to your team to manage project access.</flux:text>
                    </flux:callout>
                @else
                    <flux:checkbox.group wire:model="selectedMembers" label="Team members with access">
                        @foreach ($this->teamMembers as $member)
                            <flux:checkbox label="{{ $member->name }}" :value="$member->id" />
                        @endforeach
                    </flux:checkbox.group>
                @endif

                <div class="mt-6">
                    <flux:button wire:click="save" variant="primary" color="green">Save changes</flux:button>
                </div>
            </div>

            @if ($accessRestricted)
                <flux:callout variant="warning" icon="exclamation-triangle">
                    <flux:callout.heading>Access restricted</flux:callout.heading>
                    <flux:text>Only the selected team members can view and manage this project.</flux:text>
                </flux:callout>
            @endif
        </div>
    </div>
</div>
