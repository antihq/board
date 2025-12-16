<?php

use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create team')] class extends Component
{
    public string $name = '';

    public function create()
    {
        $this->authorize('create', Team::class);

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $team = Auth::user()->teams()->create([
            'name' => $this->name,
        ]);

        Auth::user()->switchTeam($team);

        return $this->redirect('/dashboard', navigate: true);
    }
}; ?>

<div class="mx-auto w-full max-w-[512px]">
    <flux:link href="/dashboard" class="inline-flex items-center gap-2 text-sm" variant="subtle" inline wire:navigate>
        <flux:icon.chevron-left variant="micro" />
        Dashboard
    </flux:link>

    <flux:spacer class="mt-4 lg:mt-8" />

    <form wire:submit="create">
        <flux:heading class="text-xl">Create Team</flux:heading>
        <flux:text class="mt-2">Enter a name for your new team.</flux:text>

        <flux:spacer class="mt-10" />

        <div class="space-y-6">
            <flux:input label="Team Name" placeholder="Acme Inc" wire:model="name" />
        </div>

        <flux:spacer class="mt-8" />

        <flux:button type="submit" variant="primary" color="zinc" class="w-full">Create Team</flux:button>
    </form>
</div>
