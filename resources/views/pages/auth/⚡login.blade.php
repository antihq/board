<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::auth'), Title('Login')] class extends Component
{
    //
};
?>

<div class="isolate mx-auto flex min-h-dvh max-w-7xl items-center justify-center gap-12 max-lg:flex-col">
    <div class="w-full max-w-md">
        <div class="w-full max-w-md rounded-xl bg-white shadow-md ring-1 ring-black/5">
            <div class="p-7 sm:p-11">
                <div class="space-y-8">
                    <div class="flex items-start">
                        <a href="/" wire:navigate>
                            <img src="/logo@2x.png" alt="" class="h-9" />
                        </a>
                    </div>

                    <div>
                        <flux:heading level="1" class="text-base/6! font-medium">Welcome back!</flux:heading>
                        <flux:text class="mt-1 text-sm/5">Sign in to your account to continue.</flux:text>
                    </div>

                    <livewire:one-time-pin />

                    <flux:separator text="or" />

                    <flux:button href="/register" class="w-full text-base!" wire:navigate>
                        Create an account
                    </flux:button>
                </div>
            </div>
        </div>

        @env('local')
            <div class="flex justify-center py-7 text-sm">
                <x-login-link email="oliver@antihq.com" label="Login as Oliver" :redirect-url="url('dashboard')" />
            </div>
        @endenv
    </div>
</div>
