<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="text-zinc-950 antialiased">
    <head>
        @include('partials.head', ['title' => (isset($title) ? $title.' - ' : '').config('app.name')])
    </head>
    <body>
        <flux:header container>
            <flux:brand href="/" logo="/logo.png" :name="config('app.name')" class="tracking-tight" wire:navigate />

            <flux:navbar>
                <flux:navbar.item href="/docs" :accent="false" wire:navigate>Docs</flux:navbar.item>
            </flux:navbar>

            <flux:spacer />

            <div class="flex items-center gap-x-5 md:gap-x-8">
                @guest
                    <flux:button
                        href="/register"
                        variant="primary"
                        color="zinc"
                        size="sm"
                        class="rounded-full!"
                        wire:navigate
                    >
                        Sign in
                    </flux:button>
                @else
                    <flux:button
                        href="/dashboard"
                        variant="primary"
                        color="zinc"
                        size="sm"
                        class="rounded-full!"
                        wire:navigate
                    >
                        Dashboard
                    </flux:button>
                @endguest
            </div>
        </flux:header>

        <div class="bg-linear-to-b from-white from-50% to-zinc-50">
            <flux:main container class="overflow-hidden">
                {{ $slot }}
            </flux:main>
        </div>

        <flux:toast />

        <flux:footer container class="border-black/5 bg-zinc-50 lg:border-t">
            <flux:text class="text-xs/6 lg:text-sm/6">
                <flux:link href="/" :accent="false" wire:navigate>{{ config('app.name') }}</flux:link>
                is designed, built, and backed by
                <flux:link href="https://x.com/oliverservinX" :accent="false">Oliver Servín</flux:link>
                . Need help? Send an email to
                <flux:link href="mailto:oliver@antihq.com" :accent="false">oliver@antihq.com</flux:link>
                .
            </flux:text>
        </flux:footer>

        @fluxScripts
    </body>
</html>
