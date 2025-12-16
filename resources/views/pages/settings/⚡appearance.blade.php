<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Appearance')] class extends Component
{
    //
}; ?>

<div class="mx-auto max-w-[512px]">
    <flux:link href="/dashboard" class="inline-flex items-center gap-2 text-sm" variant="subtle" inline wire:navigate>
        <flux:icon.chevron-left variant="micro" />
        Back to home
    </flux:link>

    <flux:spacer class="mt-4 lg:mt-8" />

    <section>
        <header class="flex items-center gap-3">
            <flux:heading class="text-xl">Appearance</flux:heading>
        </header>
        <flux:text class="mt-2">Choose your preferred theme.</flux:text>

        <flux:spacer class="mt-10" />

        <div class="space-y-6">
            <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
                <flux:radio value="light" icon="sun">Light</flux:radio>
                <flux:radio value="dark" icon="moon">Dark</flux:radio>
                <flux:radio value="system" icon="computer-desktop">System</flux:radio>
            </flux:radio.group>
        </div>
    </section>
</div>
