<?php

use App\Models\Team;
use Livewire\Component;

new class extends Component
{
    public Team $team;

    public function mount()
    {
        $this->authorize('view', $this->team);
    }
};
?>

<div class="mx-auto w-full max-w-3xl">
    <div class="flex justify-between gap-4">
        <flux:heading level="1" size="xl">{{ $team->name }}</flux:heading>

        <div class="flex gap-1">
            <flux:button href="{{ route('teams.settings.general', $team) }}" size="sm" wire:navigate>
                Edit Team
            </flux:button>

            <flux:button href="{{ route('teams.settings.members', $team) }}" size="sm" wire:navigate>
                Members
            </flux:button>
        </div>
    </div>
</div>
