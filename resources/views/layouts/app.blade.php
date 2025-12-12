<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="dark antialiased lg:bg-zinc-100 dark:bg-zinc-900 dark:lg:bg-zinc-950"
>
    <head>
        @include('partials.head', ['title' => (isset($title) ? $title.' - ' : '').auth()->user()->currentTeam->name.' - '.config('app.name')])
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-900 dark:lg:bg-zinc-950">
        <flux:header class="border-zinc-200 lg:border-b dark:border-zinc-700" container>
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" size="sm" />

            <div class="flex h-full items-center max-lg:hidden">
                <livewire:teams-dropdown />
                <flux:separator vertical class="mx-1 my-5" />
            </div>

            <flux:navbar class="-mb-px max-lg:hidden">
                <flux:navbar.item href="/boards" wire:navigate>Boards</flux:navbar.item>
            </flux:navbar>

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:button size="sm" variant="ghost" square>
                    <flux:avatar size="xs" :name="Auth::user()->name" color="auto" initials:single />
                </flux:button>

                <flux:menu>
                    <flux:menu.group heading="Settings">
                        <flux:menu.item href="/settings/profile" icon="user" icon:variant="micro" wire:navigate>
                            Profile
                        </flux:menu.item>
                        <flux:menu.item
                            href="/settings/appearance"
                            icon="adjustments-horizontal"
                            icon:variant="micro"
                            wire:navigate
                        >
                            Appearance
                        </flux:menu.item>
                    </flux:menu.group>

                    <form method="POST" action="/logout" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            icon:variant="micro"
                            class="w-full"
                        >
                            Log Out
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        <!-- Mobile Menu -->
        <flux:sidebar
            stashable
            sticky
            class="border-e border-zinc-200 bg-white lg:hidden dark:border-zinc-700 dark:bg-zinc-900"
        >
            <flux:sidebar.header>
                <livewire:teams-dropdown />

                <flux:sidebar.collapse
                    class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2"
                />
            </flux:sidebar.header>

            <flux:separator variant="subtle" />

            <flux:sidebar.nav>
                <flux:button href="/boards" variant="ghost" align="start" wire:navigate>Boards</flux:button>
            </flux:sidebar.nav>
        </flux:sidebar>

        <flux:main class="lg:bg-white dark:lg:bg-zinc-900" container>
            {{ $slot }}
        </flux:main>

        <flux:toast />

        <flux:footer class="border-zinc-200 lg:border-t dark:border-zinc-700" container>
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
