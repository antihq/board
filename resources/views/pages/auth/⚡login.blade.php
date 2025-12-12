<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::simple'), Title('Login')] class extends Component
{
    //
};
?>

<div class="isolate flex min-h-dvh items-center justify-center">
    <livewire:one-time-password />
</div>
