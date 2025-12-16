<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="text-zinc-950 antialiased dark:text-white">
    <head>
        @include('partials.head', [
            'title' => (isset($title) ? $title.' - ' : '').config('app.name'),
            'dark' => false,
        ])
    </head>
    <body class="bg-zinc-50 dark:bg-zinc-900">
        <flux:main>
            {{ $slot }}
        </flux:main>

        <flux:toast />

        <flux:footer>
            <flux:text class="text-center text-sm/6">
                <flux:link href="/" :accent="false" wire:navigate>{{ config('app.name') }}</flux:link>
                is designed, built, and backed by
                <flux:link href="https://x.com/oliverservinX" :accent="false">Oliver Servín</flux:link>
                . Problems or questions? Contact
                <flux:link href="mailto:support@antihq.com" :accent="false">support@antihq.com</flux:link>
                .
            </flux:text>
        </flux:footer>

        @fluxScripts
    </body>
</html>
