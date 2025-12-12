<?php

use App\Models\Team;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Team settings')] class extends Component
{
    public Team $team;

    public string $name;

    public function mount()
    {
        $this->authorize('update', $this->team);

        $this->name = $this->team->name;
    }

    public function edit()
    {
        $this->authorize('update', $this->team);

        $this->validate([
            'name' => ['required'],
        ]);

        $this->team->update(['name' => $this->name]);

        Flux::toast(
            heading: 'Saved',
            text: 'Team updated successfully.',
            variant: 'success'
        );
    }
}; ?>

<div class="mx-auto max-w-[512px]">
    <flux:link href="/dashboard" class="inline-flex items-center gap-2 text-sm" variant="subtle" inline wire:navigate>
        <flux:icon.chevron-left variant="micro" />
        Back to home
    </flux:link>

    <flux:spacer class="mt-4 lg:mt-8" />

    <form wire:submit="edit">
        <header class="flex items-center gap-3">
            <flux:heading class="text-xl">Team settings</flux:heading>
            <span class="size-1 rounded-full bg-zinc-400"></span>
            <flux:text class="text-xl">{{ $team->name }}</flux:text>
        </header>
        <flux:text class="mt-2">Update your team details.</flux:text>

        <flux:spacer class="mt-10" />

        <div class="space-y-6">
            <flux:input label="Team name" placeholder="My Team" wire:model="name" />
        </div>

        <flux:spacer class="mt-8" />

        <flux:button type="submit" variant="primary" color="zinc" class="w-full">Save changes</flux:button>
    </form>
</div>
