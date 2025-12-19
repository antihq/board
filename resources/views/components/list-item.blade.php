@blaze

@props([
    'heading' => null,
    'as' => 'div',
    'header' => null,
    'footer' => null,
])

@php
    $classes = (string) Flux::classes()
        ->add('py-3')
        ->add('bg-white dark:bg-zinc-700');

    $asButtonClasses = (string) Flux::classes()
        ->add('cursor-default select-none')
        ->add('hover:bg-zinc-50 dark:hover:bg-zinc-700 dark:hover:ring-zinc-600');
@endphp

<?php if ($as === 'button') { ?>

<ui-button
    {{ $attributes->class([$classes, $asButtonClasses]) }}
    data-flux-kanban-card
>
    @if ($header)
        <div class="mb-2 flex items-center gap-1.5 [&:not(:has(>_*))]:hidden">{{ $header }}</div>
    @endif

    <?php if ($heading) { ?>

    <flux:heading>{{ $heading }}</flux:heading>

    <?php } ?>

    {{ $slot }}

    @if ($footer)
        <div class="mt-2 flex items-center gap-1.5 [&:not(:has(>_*))]:hidden">{{ $footer }}</div>
    @endif
</ui-button>

<?php } else { ?>

<div {{ $attributes->class($classes) }} flux-kanban-card>
    @if ($header)
        <div class="mb-2 flex items-center gap-1.5 [&:not(:has(>_*))]:hidden">{{ $header }}</div>
    @endif

    <?php if ($heading) { ?>

    <flux:heading>{{ $heading }}</flux:heading>

    <?php } ?>

    {{ $slot }}

    @if ($footer)
        <div class="mt-2 flex items-center gap-1.5 [&:not(:has(>_*))]:hidden">{{ $footer }}</div>
    @endif
</div>

<?php } ?>
