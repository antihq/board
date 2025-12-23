@props(['team' => request()->team])

<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="dark antialiased lg:bg-zinc-100 dark:bg-zinc-900 dark:lg:bg-zinc-950"
>
    <head>
        @include('partials.head', ['title' => (isset($title) ? $title . ' - ' : '') . $team?->name . ' - ' . config('app.name')])
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-900 dark:lg:bg-zinc-950">
        <flux:header class="border-zinc-200 lg:border-b dark:border-zinc-700">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" size="sm" />

            <flux:navbar class="-mb-px max-lg:hidden">
                @if ($team)
                    <livewire:teams-dropdown :team="$team" />
                    <flux:separator vertical variant="subtle" class="mx-1 my-1" />
                    <flux:navbar.item href="{{ route('teams.show', $team) }}">Home</flux:navbar.item>
                    <livewire:projects-dropdown :team="$team" />
                    <flux:dropdown>
                        <flux:navbar.item icon:trailing="chevron-down">Tasks</flux:navbar.item>
                        <flux:navmenu>
                            <flux:navmenu.item
                                href="{{ route('tasks', [$team, 'assigned_to' => [auth()->user()->id]]) }}"
                                wire:navigate
                            >
                                Assigned to me
                            </flux:navmenu.item>
                            <flux:navmenu.item
                                href="{{ route('tasks', [$team, 'added_by' => [auth()->user()->id]]) }}"
                                wire:navigate
                            >
                                Added by me
                            </flux:navmenu.item>
                            <flux:navmenu.item href="{{ route('teams.saved-tasks', $team) }}" wire:navigate>
                                Saved tasks
                            </flux:navmenu.item>
                            <flux:menu.separator />
                            <flux:navmenu.item href="{{ route('tasks', $team) }}" wire:navigate>
                                All tasks
                            </flux:navmenu.item>
                        </flux:navmenu>
                    </flux:dropdown>
                    <flux:dropdown>
                        <flux:navbar.item icon:trailing="chevron-down">Tags</flux:navbar.item>
                        <flux:navmenu>
                            @foreach ($team->tags()->orderBy('name')->get() as $tag)
                                <flux:navmenu.item
                                    href="{{ route('tasks', [$team, 'tags' => [$tag->id]]) }}"
                                    wire:navigate
                                >
                                    {{ $tag->name }}
                                </flux:navmenu.item>
                            @endforeach

                            @if ($team->tags()->count() === 0)
                                <flux:navmenu.item disabled>No tags created</flux:navmenu.item>
                            @endif
                        </flux:navmenu>
                    </flux:dropdown>
                    <flux:dropdown>
                        <flux:navbar.item icon:trailing="chevron-down">Members</flux:navbar.item>
                        <flux:navmenu>
                            <flux:modal name="invite-people" class="w-full max-w-[95vw] md:w-[600px]">
                                <x-slot name="trigger">
                                    <flux:navmenu.item>Invite people</flux:navmenu.item>
                                </x-slot>
                                <div class="space-y-6">
                                    <div>
                                        <flux:heading size="lg">Invite people to {{ $team->name }}</flux:heading>
                                        <flux:text class="mt-2">
                                            Share this link with people you want to invite to join your team.
                                        </flux:text>
                                    </div>

                                    <flux:input
                                        readonly
                                        copyable
                                        :value="route('teams.join', [$team, $team->invitation_code])"
                                        label="Team invite link"
                                    />
                                </div>
                            </flux:modal>
                        </flux:navmenu>
                    </flux:dropdown>
                    <flux:navbar.item :href="route('teams.settings.general', $team)" wire:navigate>
                        Settings
                    </flux:navbar.item>
                @endif
            </flux:navbar>

            {{-- <flux:separator vertical class="mx-1 my-5" /> --}}

            <flux:navbar class="-mb-px max-lg:hidden">
                {{--  --}}
            </flux:navbar>

            <flux:spacer />

            <flux:navbar class="me-4">
                @if ($team)
                    <flux:navbar.item
                        icon="bookmark"
                        href="{{ route('teams.saved-tasks', $team) }}"
                        label="Bookmarks"
                        wire:navigate
                    />
                @else
                    <flux:navbar.item icon="bookmark" disabled label="Bookmarks" />
                @endif
                <flux:navbar.item icon="magnifying-glass" disabled label="Search" />
                <flux:navbar.item icon="inbox" disabled label="inbox" />
            </flux:navbar>

            <flux:dropdown position="top" align="end">
                <flux:button size="sm" variant="ghost" square>
                    <flux:avatar
                        size="xs"
                        src="https://unavatar.io/gravatar/{{ auth()->user()->email }}"
                        :name="Auth::user()->name"
                        color="auto"
                        initials:single
                    />
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
                        <flux:menu.item
                            href="/settings/devices"
                            icon="device-phone-mobile"
                            icon:variant="micro"
                            wire:navigate
                        >
                            Devices
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
                @if ($team)
                    <livewire:teams-dropdown :$team />
                @endif

                <flux:sidebar.collapse
                    class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2"
                />
            </flux:sidebar.header>

            <flux:separator variant="subtle" />

            <flux:sidebar.nav>
                @if ($team)
                    <flux:sidebar.item href="{{ route('teams.show', $team) }}" current>Home</flux:sidebar.item>
                    <livewire:projects-dropdown :team="$team" />
                    <flux:sidebar.group expandable heading="Tasks" class="grid">
                        <flux:sidebar.item
                            href="{{ route('tasks', [$team, 'assigned_to' => [auth()->user()->id]]) }}"
                            wire:navigate
                        >
                            Assigned to me
                        </flux:sidebar.item>
                        <flux:sidebar.item
                            href="{{ route('tasks', [$team, 'added_by' => [auth()->user()->id]]) }}"
                            wire:navigate
                        >
                            Added by me
                        </flux:sidebar.item>
                        <flux:sidebar.item href="{{ route('teams.saved-tasks', $team) }}" wire:navigate>
                            Saved tasks
                        </flux:sidebar.item>
                        <flux:sidebar.item href="{{ route('tasks', $team) }}" wire:navigate>
                            All tasks
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                    <flux:sidebar.group expandable heading="Tags" class="grid">
                        @foreach ($team->tags()->orderBy('name')->get() as $tag)
                            <flux:sidebar.item
                                href="{{ route('tasks', [$team, 'tags' => [$tag->id]]) }}"
                                wire:navigate
                            >
                                {{ $tag->name }}
                            </flux:sidebar.item>
                        @endforeach

                        @if ($team->tags()->count() === 0)
                            <flux:sidebar.item disabled>No tags created</flux:sidebar.item>
                        @endif
                    </flux:sidebar.group>
                    <flux:sidebar.group expandable heading="Members" class="grid">
                        <flux:modal name="invite-people-mobile" class="w-full max-w-[95vw] md:w-[600px]">
                            <x-slot name="trigger">
                                <flux:sidebar.item>Invite people</flux:sidebar.item>
                            </x-slot>
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Invite people to {{ $team->name }}</flux:heading>
                                    <flux:text class="mt-2">
                                        Share this link with people you want to invite to join your team.
                                    </flux:text>
                                </div>

                                <flux:input
                                    readonly
                                    copyable
                                    :value="route('teams.join', [$team, $team->invitation_code])"
                                    label="Team invite link"
                                />
                            </div>
                        </flux:modal>
                    </flux:sidebar.group>
                    <flux:sidebar.item :href="route('teams.settings.general', $team)" wire:navigate>
                        Settings
                    </flux:sidebar.item>
                @endif
            </flux:sidebar.nav>
        </flux:sidebar>

        <flux:main class="lg:bg-white dark:lg:bg-zinc-900" container>
            {{ $slot }}
        </flux:main>

        @persist('toast')
            <flux:toast position="bottom center" />
        @endpersist

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
