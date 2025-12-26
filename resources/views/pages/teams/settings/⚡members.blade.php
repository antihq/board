<?php

use App\Models\Team;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component {
    public Team $team;

    #[Validate('required|integer|min:1')]
    public $invitation_code_max_uses;

    public function mount()
    {
        $this->authorize('view', $this->team);
        $this->invitation_code_max_uses = $this->team->invitation_code_max_uses;
    }

    public function saveMaxUses()
    {
        $this->authorize('update', $this->team);

        $this->validate();

        $this->team->update([
            'invitation_code_max_uses' => $this->invitation_code_max_uses,
        ]);

        Flux::toast('Max uses updated', variant: 'success');
    }

    public function regenerateInvitationCode()
    {
        $this->authorize('update', $this->team);

        $this->team->regenerateInvitationCode();
        $this->team->refresh();

        Flux::toast('Invitation link regenerated', variant: 'success');
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

    public function canChangeMemberRole(User $member): bool
    {
        if ($member->id === $this->team->owner->id) {
            return false;
        }

        $currentUser = Auth::user();

        if (! $currentUser) {
            return false;
        }

        $isOwner = $currentUser->id === $this->team->owner->id;

        if ($isOwner) {
            return true;
        }

        $currentUserIsAdmin = $this->team
            ->users()
            ->where('users.id', $currentUser->id)
            ->where('team_members.role', 'admin')
            ->exists();

        if (! $currentUserIsAdmin) {
            return false;
        }

        $memberIsAdmin = $this->team
            ->users()
            ->where('users.id', $member->id)
            ->where('team_members.role', 'admin')
            ->exists();

        return ! $memberIsAdmin;
    }

    public function toggleMemberRole($userId)
    {
        $member = User::find($userId);
        $currentUser = Auth::user();

        if (! $member || ! $currentUser) {
            return;
        }

        if (! $this->canChangeMemberRole($member)) {
            return;
        }

        $currentRole = $this->team
            ->users()
            ->where('users.id', $member->id)
            ->first()?->pivot->role ?? 'member';

        $newRole = $currentRole === 'member' ? 'admin' : 'member';

        $this->team->users()->updateExistingPivot($member->id, ['role' => $newRole]);
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
                <flux:navbar.item :href="route('teams.settings.members', $team)" :accent="true" wire:navigate>
                    Members
                </flux:navbar.item>
                <flux:navbar.item :href="route('teams.settings.auto-close', $team)" :accent="false" wire:navigate>
                    Auto-close
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

        <div class="max-w-lg space-y-3">
            @foreach ($this->teamMembers as $member)
                <div class="flex items-center gap-3">
                    <flux:avatar
                        :src="$member->profilePhotoUrl()"
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
                        @if ($this->canChangeMemberRole($member))
                            @php
                                $memberWithPivot = $this->team
                                    ->users()
                                    ->where('users.id', $member->id)
                                    ->first();
                                $currentRole = $memberWithPivot && $memberWithPivot->pivot ? $memberWithPivot->pivot->role : 'member';
                                $newRole = $currentRole === 'member' ? 'admin' : 'member';
                            @endphp
                            <flux:button
                                size="sm"
                                wire:click="toggleMemberRole({{ $member->getKey() }})"
                            >
                                Make {{ ucfirst($newRole) }}
                            </flux:button>
                        @endif

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

        <flux:modal name="invite-member" class="w-full max-w-[95vw] md:w-[600px]">
            <x-slot name="trigger">
                <flux:button>Invite people</flux:button>
            </x-slot>
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Invite people to {{ $team->name }}</flux:heading>
                    <flux:text class="mt-2">
                        Share this link with people you want to invite to join your team.
                    </flux:text>
                </div>

                <div class="space-y-3">
                    <flux:input
                        readonly
                        copyable
                        :value="route('teams.join', [$team, $team->invitation_code])"
                        label="Team invite link"
                    />

                    <div class="flex gap-2">
                        <flux:modal.trigger name="invite-qr">
                            <flux:button variant="outline" size="sm">Show QR code</flux:button>
                        </flux:modal.trigger>

                        <flux:button
                            wire:click="regenerateInvitationCode"
                            wire:confirm="Are you sure you want to generate a new link? The previous code will stop working."
                            size="sm"
                        >
                            Regenerate
                        </flux:button>
                    </div>
                </div>

                <form wire:submit="saveMaxUses" class="space-y-3">
                    <flux:field>
                        <flux:label>Max uses</flux:label>
                        <flux:description>How many people can use this link</flux:description>
                        <flux:input wire:model="invitation_code_max_uses" />
                        <flux:description>
                            Used {{ $team->invitation_code_uses_count }}/{{ $invitation_code_max_uses }}
                            {{ Str::plural('time', $team->invitation_code_uses_count) }}
                        </flux:description>
                        <flux:error name="invitation_code_max_uses" />
                    </flux:field>

                    <flux:button type="submit" size="sm">Save</flux:button>
                </form>
            </div>
        </flux:modal>

        <flux:modal name="invite-qr" class="w-full max-w-[95vw] md:w-[500px]">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Invite QR Code</flux:heading>
                    <flux:text class="mt-2">Scan this QR code to join {{ $team->name }}</flux:text>
                </div>

                <div class="flex justify-center rounded-lg bg-zinc-50 p-6 dark:bg-zinc-800">
                    <img
                        src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode(route('teams.join', [$team, $team->invitation_code])) }}"
                        alt="Team invite QR code"
                        class="rounded-md"
                    />
                </div>
            </div>
        </flux:modal>
    </div>
</div>
