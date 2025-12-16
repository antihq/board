<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Home')] class extends Component
{
    #[Computed]
    public function team()
    {
        return Auth::user()->currentTeam;
    }
};
?>

<div>
    <header class="flex items-center">
        <div class="flex items-center gap-3">
            <flux:avatar
                :name="$this->team->name"
                color="auto"
                initials:single
                :color:seed="'team-' . $this->team->name"
            />
            <flux:heading class="text-xl">{{ $this->team->name }}</flux:heading>
        </div>
        <flux:spacer />
        <flux:dropdown align="end">
            <flux:button icon:trailing="ellipsis-horizontal" size="sm" variant="subtle" />

            <flux:menu>
                <flux:menu.group heading="Settings">
                    <flux:menu.item
                        href="/teams/{{ $this->team->id }}/settings/general"
                        icon="cog-8-tooth"
                        icon:variant="micro"
                        wire:navigate
                    >
                        General
                    </flux:menu.item>
                    <flux:menu.item
                        href="/teams/{{ $this->team->id }}/settings/members"
                        icon="user-group"
                        icon:variant="micro"
                        wire:navigate
                    >
                        Members
                    </flux:menu.item>
                </flux:menu.group>
                @if ($this->team->subscribed())
                    <flux:menu.group heading="Billing">
                        <flux:menu.item href="/billing-portal" icon="credit-card" icon:variant="micro">
                            Manage
                        </flux:menu.item>
                    </flux:menu.group>
                @endif
            </flux:menu>
        </flux:dropdown>
    </header>
</div>
