<?php

use App\Models\Team;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component {
    public Team $team;

    #[Validate('required|integer|in:3,7,11,30,90,395')]
    public $auto_close_days;

    public function mount()
    {
        $this->authorize('update', $this->team);

        $this->auto_close_days = $this->team->auto_close_days ?? 30;
    }

    public function save()
    {
        $this->validate();

        $this->team->update([
            'auto_close_days' => $this->auto_close_days,
        ]);

        Flux::toast('Auto-close settings saved', variant: 'success');
    }

    #[Computed]
    public function autoCloseOptions()
    {
        return [
            3 => '3 days',
            7 => '1 week',
            11 => '11 days',
            30 => '30 days',
            90 => '3 months',
            395 => '1 year',
        ];
    }
};
?>

<div class="space-y-6">
    <flux:heading level="1" size="lg">Team settings</flux:heading>

    <div class="space-y-8">
        <div class="border-b border-zinc-200 dark:border-zinc-700">
            <flux:navbar class="-mb-px">
                <flux:navbar.item :href="route('teams.settings.general', $team)" :accent="false" wire:navigate>
                    General
                </flux:navbar.item>
                <flux:navbar.item :href="route('teams.settings.members', $team)" :accent="false" wire:navigate>
                    Members
                </flux:navbar.item>
                <flux:navbar.item :href="route('teams.settings.auto-close', $team)" :accent="true" wire:navigate>
                    Auto-close
                </flux:navbar.item>
            </flux:navbar>
        </div>

        <div class="max-w-lg">
            <form wire:submit="save" class="space-y-6">
                <flux:select
                    wire:model="auto_close_days"
                    label="Auto-close inactive tasks after"
                    description="Tasks with no activity for this period will be automatically closed."
                    required
                >
                    @foreach ($this->autoCloseOptions as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:callout variant="secondary" icon="information-circle">
                    <flux:callout.heading>How it works</flux:callout.heading>
                    <flux:text>
                        Tasks will be automatically closed if there are no comments, status changes, or other activity
                        for the specified period. Individual projects can override this setting.
                    </flux:text>
                </flux:callout>

                <div class="flex gap-3">
                    <flux:button type="submit" variant="primary">Save</flux:button>
                </div>
            </form>
        </div>
    </div>
</div>
