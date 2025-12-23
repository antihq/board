<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public $team;

    public function mount()
    {
        $this->authorize('view', $this->team);
    }

    #[Computed]
    public function teamMembers()
    {
        $members = collect();

        $members->push($this->team->owner);

        $members = $members->merge($this->team->users);

        return $members->unique('id');
    }

    #[Computed]
    public function memberRoles()
    {
        $roles = collect();

        foreach ($this->teamMembers as $member) {
            if ($member->id === $this->team->owner->id) {
                $roles[$member->id] = 'owner';
            } else {
                $roles[$member->id] =
                    $this->team
                        ->users()
                        ->where('users.id', $member->id)
                        ->first()?->pivot->role ?? 'member';
            }
        }

        return $roles;
    }

    public function removeTeamMember($userId)
    {
        $member = User::find($userId);
        $currentUser = Auth::user();

        if (! $member || ! $currentUser) {
            return;
        }

        if ($member->id === $this->team->owner->id) {
            return;
        }

        $isOwner = $currentUser->id === $this->team->owner->id;
        $isAdmin = $this->team
            ->users()
            ->where('users.id', $currentUser->id)
            ->where('team_members.role', 'admin')
            ->exists();

        if (! $isOwner && ! $isAdmin) {
            return;
        }

        $this->team->users()->detach($member->id);
    }

    public function leaveTeam()
    {
        $currentUser = Auth::user();

        if (! $currentUser) {
            return;
        }

        if ($currentUser->id === $this->team->owner->id) {
            return;
        }

        $this->team->users()->detach($currentUser->id);

        return redirect()->route('dashboard');
    }

    public function canRemoveMember(User $member): bool
    {
        if ($member->id === $this->team->owner->id) {
            return false;
        }

        $currentUser = Auth::user();

        if (! $currentUser) {
            return false;
        }

        $isOwner = $currentUser->id === $this->team->owner->id;
        $isAdmin = $this->team
            ->users()
            ->where('users.id', $currentUser->id)
            ->where('team_members.role', 'admin')
            ->exists();

        return $isOwner || $isAdmin;
    }

    public function canLeaveTeam(): bool
    {
        $currentUser = Auth::user();

        if (! $currentUser) {
            return false;
        }

        if ($currentUser->id === $this->team->owner->id) {
            return false;
        }

        return $this->team
            ->users()
            ->where('users.id', $currentUser->id)
            ->exists();
    }
};
?>

<div class="space-y-6">
    <flux:heading level="1" size="lg">Team settings</flux:heading>

    <div class="space-y-8">
        <div class="border-b border-zinc-200 dark:border-zinc-700">
            <flux:navbar class="-mb-px">
                <flux:navbar.item :href="route('teams.settings.general', $team)" :accent="false" wire:navigate>
                    General
                </flux:navbar.item>
                <flux:navbar.item :href="route('teams.settings.auto-close', $team)" :accent="false" wire:navigate>
                    Auto-close
                </flux:navbar.item>
                <flux:navbar.item :href="route('teams.settings.members', $team)" :accent="true" wire:navigate>
                    Members
                </flux:navbar.item>
            </flux:navbar>
        </div>

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
                        <div class="flex items-center gap-1">
                            <flux:heading>
                                {{ $member->name }}
                            </flux:heading>

                            @if ($this->team->owner && $member->id === $this->team->owner->id)
                                <flux:badge size="sm" color="green">Owner</flux:badge>
                            @else
                                @php
                                    $memberWithPivot = $this->team
                                        ->users()
                                        ->where('users.id', $member->id)
                                        ->first();
                                @endphp

                                @if ($memberWithPivot && $memberWithPivot->pivot && $memberWithPivot->pivot->role === 'admin')
                                    <flux:badge size="sm" color="blue">Admin</flux:badge>
                                @endif
                            @endif
                        </div>
                        <flux:text size="sm">{{ $member->email }}</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($this->canRemoveMember($member))
                            <flux:button
                                size="sm"
                                wire:click="removeTeamMember({{ $member->getKey() }})"
                                wire:confirm="Are you sure you want to remove {{ $member->name }} from the team?"
                            >
                                Remove
                            </flux:button>
                        @elseif ($member->id === auth()->id() && $this->canLeaveTeam())
                            <flux:button
                                size="sm"
                                wire:click="leaveTeam"
                                wire:confirm="Are you sure you want to leave this team?"
                            >
                                Leave
                            </flux:button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
