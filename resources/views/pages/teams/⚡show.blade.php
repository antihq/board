<?php

use App\Models\Team;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public Team $team;

    public function mount()
    {
        $this->authorize('view', $this->team);
    }

    #[Computed]
    public function teamMembers()
    {
        $members = collect();

        // Add the owner
        $members->push($this->team->owner);

        // Add all team members
        $members = $members->merge($this->team->users);

        return $members->unique('id');
    }
};
?>

<div class="mx-auto w-full max-w-3xl">
    <div class="flex justify-between gap-4">
        <flux:heading level="1" size="xl">{{ $team->name }}</flux:heading>

        <div class="flex gap-1">
            <flux:modal class="w-full max-w-[95vw] md:w-[600px]">
                <x-slot name="trigger">
                    <flux:button size="sm">Settings</flux:button>
                </x-slot>
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">Team members</flux:heading>
                        <flux:text class="mt-2">
                            {{ $team->name }} has {{ $this->teamMembers->count() }}
                            member{{ $this->teamMembers->count() !== 1 ? 's' : '' }}
                        </flux:text>
                    </div>

                    <div class="space-y-3">
                        @foreach ($this->teamMembers as $member)
                            <div class="flex items-center gap-3">
                                <flux:avatar
                                    :src="$member->avatar_url ?? null"
                                    :name="$member->name"
                                    :color:seed="'u'.$member->id"
                                    color="auto"
                                />
                                <div class="flex-1">
                                    <flux:heading>{{ $member->name }}</flux:heading>
                                    <flux:text size="sm">{{ $member->email }}</flux:text>
                                </div>
                                @if ($member->id === $team->owner->id)
                                    <flux:badge size="sm" color="green">Owner</flux:badge>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </flux:modal>
        </div>
    </div>
</div>
