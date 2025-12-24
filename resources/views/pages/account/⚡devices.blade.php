<?php

use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Device settings')] class extends Component
{
    public Team $team;

    #[Computed]
    public function deviceLoginUrl()
    {
        return URL::signedRoute(
            'auth.device-login',
            [
                'user' => Auth::id(),
            ],
            now()->addMinutes(30),
        );
    }
}; ?>

<div>
    <div class="space-y-6">
        <flux:heading level="1" size="lg">Settings</flux:heading>

        <div class="space-y-8">
            <div class="border-b border-zinc-200 dark:border-zinc-700">
                <flux:navbar class="-mb-px">
                    <flux:navbar.item :href="route('teams.account.profile', $team)" :accent="false" wire:navigate>
                        Profile
                    </flux:navbar.item>
                    <flux:navbar.item :href="route('teams.account.appearance', $team)" :accent="false" wire:navigate>
                        Appearance
                    </flux:navbar.item>
                    <flux:navbar.item :href="route('teams.account.devices', $team)" :accent="true" wire:navigate>
                        Devices
                    </flux:navbar.item>
                </flux:navbar>
            </div>

            <div>
                <flux:heading size="lg">Login on another device</flux:heading>
                <flux:text class="mt-2">
                    Use this secure link to log in to your account on another device. The link expires in 30 minutes.
                </flux:text>
            </div>

            <div class="max-w-lg space-y-3">
                <flux:input readonly copyable :value="$this->deviceLoginUrl" label="Device login link" />

                <div class="flex gap-2">
                    <flux:modal.trigger name="device-qr">
                        <flux:button variant="outline">Show QR code</flux:button>
                    </flux:modal.trigger>
                </div>
            </div>
        </div>
    </div>

    <flux:modal name="device-qr" class="w-full max-w-[95vw] md:w-[500px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Login QR Code</flux:heading>
                <flux:text class="mt-2">Scan this QR code to log in on another device</flux:text>
            </div>

            <div class="flex justify-center rounded-lg bg-zinc-50 p-6 dark:bg-zinc-800">
                <img
                    src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($this->deviceLoginUrl) }}"
                    alt="Device login QR code"
                    class="rounded-md"
                />
            </div>
        </div>
    </flux:modal>
</div>
