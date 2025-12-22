<?php

use App\Models\Project;
use App\Models\Team;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    public Team $team;

    public Project $project;

    #[Validate('nullable|integer|in:3,7,11,30,90,395')]
    public $auto_close_days;

    public function mount()
    {
        $this->authorize('update', $this->project);

        $this->auto_close_days = $this->project->auto_close_days;
    }

    public function save()
    {
        $this->validate();

        $this->project->update([
            'auto_close_days' => $this->auto_close_days ?: null,
        ]);

        Flux::toast('Auto-close settings saved', variant: 'success');
    }

    #[Computed]
    public function autoCloseOptions()
    {
        return [
            '' => 'Use team default',
            3 => '3 days',
            7 => '1 week',
            11 => '11 days',
            30 => '30 days',
            90 => '3 months',
            395 => '1 year',
        ];
    }

    #[Computed]
    public function teamDefaultDays()
    {
        return $this->project->team->auto_close_days ?? 30;
    }
};
?>

<div class="space-y-6">
    <flux:heading level="1" size="lg">Project settings</flux:heading>

    <div class="space-y-8">
        <div class="border-b border-zinc-200 dark:border-zinc-700">
            <flux:navbar class="-mb-px">
                <flux:navbar.item
                    :href="route('projects.settings.general', [$project->team, $project])"
                    :accent="false"
                    wire:navigate
                >
                    General
                </flux:navbar.item>
                <flux:navbar.item
                    :href="route('projects.settings.auto-close', [$project->team, $project])"
                    :accent="true"
                    wire:navigate
                >
                    Auto-close
                </flux:navbar.item>
            </flux:navbar>
        </div>

        <div class="max-w-lg">
            <form wire:submit="save" class="space-y-6">
                <flux:select
                    wire:model="auto_close_days"
                    label="Auto-close inactive tasks after"
                    description="Override the team setting for this project. If left empty, the team default will be used."
                >
                    @foreach ($this->autoCloseOptions as $days => $label)
                        <flux:select.option value="{{ $days }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                @if (! $auto_close_days)
                    <flux:callout variant="secondary" icon="information-circle">
                        <flux:callout.heading>Team default in use</flux:callout.heading>
                        <flux:text>
                            This project is currently using the team default of {{ $this->teamDefaultDays }} days.
                        </flux:text>
                    </flux:callout>
                @endif

                <flux:callout variant="secondary" icon="information-circle">
                    <flux:callout.heading>How it works</flux:callout.heading>
                    <flux:text>
                        Tasks will be automatically closed if there are no comments, status changes, or other activity
                        for the specified period.
                    </flux:text>
                </flux:callout>

                <div class="flex gap-3">
                    <flux:button type="submit" variant="primary" color="green">Save</flux:button>
                </div>
            </form>
        </div>
    </div>
</div>
