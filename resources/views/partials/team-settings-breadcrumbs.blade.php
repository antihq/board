<flux:breadcrumbs>
    <flux:breadcrumbs.item href="/dashboard" wire:navigate>{{ $team->name }}</flux:breadcrumbs.item>
    <flux:breadcrumbs.item href="/teams/{{ $team->id }}/settings/general" wire:navigate>
        Settings
    </flux:breadcrumbs.item>
    <flux:breadcrumbs.item>{{ $current }}</flux:breadcrumbs.item>
</flux:breadcrumbs>
