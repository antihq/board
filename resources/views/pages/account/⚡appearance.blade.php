<?php

use App\Models\Team;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Appearance')] class extends Component
{
    public Team $team;
}; ?>

<div class="space-y-6">
    <flux:heading level="1" size="lg">Settings</flux:heading>

    <div class="space-y-8">
        <div class="border-b border-zinc-200 dark:border-zinc-700">
            <flux:navbar class="-mb-px">
                <flux:navbar.item :href="route('teams.account.profile', $team)" :accent="false" wire:navigate>
                    Profile
                </flux:navbar.item>
                <flux:navbar.item :href="route('teams.account.appearance', $team)" :accent="true" wire:navigate>
                    Appearance
                </flux:navbar.item>
                <flux:navbar.item :href="route('teams.account.devices', $team)" :accent="false" wire:navigate>
                    Devices
                </flux:navbar.item>
            </flux:navbar>
        </div>

        <div class="max-w-lg">
            <div class="space-y-6">
                <flux:heading size="lg">Appearance</flux:heading>
                <flux:text>Choose your preferred theme.</flux:text>

                <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
                    <flux:radio value="light" icon="sun">Light</flux:radio>
                    <flux:radio value="dark" icon="moon">Dark</flux:radio>
                    <flux:radio value="system" icon="computer-desktop">System</flux:radio>
                </flux:radio.group>
            </div>
        </div>
    </div>
</div>
