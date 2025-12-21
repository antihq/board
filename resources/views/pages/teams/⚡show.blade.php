<?php

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
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

        if (! $member) {
            return;
        }

        // Cannot remove the team owner
        if ($member->id === $this->team->owner->id) {
            return;
        }

        // Check if current user can remove members (owner or admin)
        $currentUser = Auth::user();
        $isOwner = $currentUser->id === $this->team->owner->id;
        $isAdmin = $this->team
            ->users()
            ->where('users.id', $currentUser->id)
            ->where('team_members.role', 'admin')
            ->exists();

        if (! $isOwner && ! $isAdmin) {
            return;
        }

        // Remove the member from the team
        $this->team->users()->detach($member->id);
    }

    public function leaveTeam()
    {
        $currentUser = Auth::user();

        // Owner cannot leave the team
        if ($currentUser->id === $this->team->owner->id) {
            return;
        }

        // Remove current user from the team
        $this->team->users()->detach($currentUser->id);

        // Redirect to dashboard after leaving
        return redirect()->route('dashboard');
    }

    public function canRemoveMember(User $member): bool
    {
        // Cannot remove the team owner
        if ($member->id === $this->team->owner->id) {
            return false;
        }

        $currentUser = Auth::user();
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

        // Owner cannot leave the team
        if ($currentUser->id === $this->team->owner->id) {
            return false;
        }

        // Any member (admin or regular member) can leave the team
        return $this->team
            ->users()
            ->where('users.id', $currentUser->id)
            ->exists();
    }
};
?>

<div class="mx-auto w-full max-w-3xl">
    <div class="flex justify-between gap-4">
        <flux:heading level="1" size="xl">{{ $team->name }}</flux:heading>

        <div class="flex gap-1">
            <flux:button href="{{ route('teams.settings.general', $team) }}" size="sm" wire:navigate>
                Edit Team
            </flux:button>

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
                                    <div class="flex items-center gap-1">
                                        <flux:heading>
                                            {{ $member->name }}
                                        </flux:heading>

                                        @if ($this->memberRoles[$member->id] === 'owner')
                                            <flux:badge size="sm" color="green">Owner</flux:badge>
                                        @elseif ($this->memberRoles[$member->id] === 'admin')
                                            <flux:badge size="sm" color="blue">Admin</flux:badge>
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
            </flux:modal>
        </div>
    </div>
</div>
