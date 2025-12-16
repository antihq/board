<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::auth'), Title('Login')] class extends Component
{
    //
};
?>

<div class="isolate flex min-h-dvh flex-col items-center justify-center">
    <livewire:one-time-password />

    @env('local')
        <div class="py-7 text-sm">
            <x-login-link email="oliver@example.com" label="Login as Oliver" :redirect-url="url('dashboard')" />
        </div>
    @endenv
</div>
