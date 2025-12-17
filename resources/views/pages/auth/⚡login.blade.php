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
    <div>
        <h1
            class="text-5xl/[0.9] font-medium tracking-tight text-balance hyphens-auto text-zinc-950 max-lg:text-center sm:text-8xl/[0.8] md:text-9xl/[0.8]"
        >
            Kanban, decaffeinated.
        </h1>
    </div>

    <div class="w-full max-w-md">
        <livewire:one-time-password />

        @env('local')
            <div class="flex justify-center py-7 text-sm">
                <x-login-link email="oliver@antihq.com" label="Login as Oliver" :redirect-url="url('dashboard')" />
            </div>
        @endenv
    </div>
</div>
