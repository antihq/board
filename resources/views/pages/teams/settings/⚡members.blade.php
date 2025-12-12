<?php

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Notifications\TeamInvitation as TeamInvitationNotification;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Team members')] class extends Component
{
    public Team $team;

    public Collection $invitations;

    public Collection $members;

    public string $email = '';

    public function mount()
    {
        $this->authorize('manageMembers', $this->team);

        $this->invitations = $this->getInvitations();
        $this->members = $this->getMembers();
    }

    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                Rule::unique('team_invitations', 'email')->where(
                    fn ($query) => $query->where('team_id', $this->team->id)
                ),
            ],
        ];
    }

    public function sendInvitation()
    {
        $this->validate();

        $invitation = $this->team->inviteMember($this->email);

        Notification::route('mail', $this->email)
            ->notify(new TeamInvitationNotification($invitation));

        Flux::toast(
            heading: 'Invitation sent',
            text: 'The invitation was sent to '.$this->email.'.',
            variant: 'success'
        );

        $this->reset('email');

        $this->invitations = $this->getInvitations();
    }

    private function getInvitations(): Collection
    {
        return $this->team->invitations()->latest()->get();
    }

    public function removeMember(User $member): void
    {
        abort_unless($this->team->isMember($member), 403);

        $this->team->removeMember($member);

        Flux::toast(
            heading: 'Member removed',
            text: 'The member '.$member->name.' was removed from team.',
            variant: 'success'
        );

        $this->members = $this->getMembers();
    }

    private function getMembers(): Collection
    {
        return $this->team->members()->get();
    }

    public function revokeInvitation(TeamInvitation $invitation): void
    {
        $this->authorize('revoke', $invitation);

        $invitation->delete();

        Flux::toast(
            heading: 'Invitation revoked',
            text: 'The invitation for '.$invitation->email.' was revoked.',
            variant: 'success'
        );

        $this->invitations = $this->getInvitations();
    }
}; ?>

<div class="mx-auto max-w-[512px]">
    <flux:link href="/dashboard" class="inline-flex items-center gap-2 text-sm" variant="subtle" inline wire:navigate>
        <flux:icon.chevron-left variant="micro" />
        Back to home
    </flux:link>

    <flux:spacer class="mt-4 lg:mt-8" />

    <section>
        <header class="flex items-center gap-3">
            <flux:heading class="text-xl">Team Members</flux:heading>
            <span class="size-1 rounded-full bg-zinc-400"></span>
            <flux:text class="text-xl">{{ $team->name }}</flux:text>
        </header>
        <flux:text class="mt-2">Manage your team members and invitations.</flux:text>

        <flux:spacer class="mt-10" />
        <div class="space-y-8">
            <div>
                <flux:heading size="lg">Current Members</flux:heading>
                <flux:table class="mt-4">
                    <flux:table.columns>
                        <flux:table.column>Member</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        <flux:table.row>
                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    <flux:avatar :name="$team->user->name" size="sm" circle />
                                    <div class="flex flex-col">
                                        <flux:heading size="sm">
                                            {{ $team->user->name }}
                                            <flux:badge size="sm" color="yellow" class="ml-2">Owner</flux:badge>
                                        </flux:heading>
                                        <flux:text class="text-sm">{{ $team->user->email }}</flux:text>
                                    </div>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                        @foreach ($members as $member)
                            <flux:table.row>
                                <flux:table.cell>
                                    <div class="flex items-center gap-3">
                                        <flux:avatar :name="$member->name" size="sm" circle />
                                        <div class="flex flex-col">
                                            <flux:heading size="sm">{{ $member->name }}</flux:heading>
                                            <flux:text class="text-sm">{{ $member->email }}</flux:text>
                                        </div>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell align="end">
                                    <flux:button
                                        variant="subtle"
                                        size="sm"
                                        wire:click="removeMember({{ $member->id }})"
                                    >
                                        Remove
                                    </flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>

            <div>
                <flux:heading size="lg">Invite Member</flux:heading>
                <flux:text class="mt-2 text-sm">
                    Invite a new member to your team by entering their email address.
                </flux:text>
                <form wire:submit="sendInvitation" class="mt-4 space-y-4">
                    <flux:field>
                        <flux:label>Email address</flux:label>
                        <flux:input.group>
                            <flux:input type="email" wire:model="email" placeholder="Enter member email" icon="user" />
                            <flux:button type="submit">Send Invitation</flux:button>
                        </flux:input.group>
                        <flux:error name="email" />
                    </flux:field>
                </form>
            </div>

            <div>
                <flux:heading size="lg">Pending Invitations</flux:heading>
                <flux:text class="mt-2 text-sm">Manage invitations you have sent to join your team.</flux:text>
                <div class="mt-4">
                    @if ($invitations->isEmpty())
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <flux:icon name="user-plus" variant="mini" class="mb-4 text-gray-400" />
                            <flux:heading size="md" class="mb-1">No pending invitations</flux:heading>
                            <flux:text class="text-sm">You haven't sent any invitations yet.</flux:text>
                        </div>
                    @else
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>Invitation</flux:table.column>
                            </flux:table.columns>
                            <flux:table.rows>
                                @foreach ($invitations as $invitation)
                                    <flux:table.row>
                                        <flux:table.cell>{{ $invitation->email }}</flux:table.cell>
                                        <flux:table.cell align="end">
                                            <flux:button
                                                color="danger"
                                                variant="subtle"
                                                size="sm"
                                                wire:click="revokeInvitation({{ $invitation->id }})"
                                            >
                                                Revoke
                                            </flux:button>
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    @endif
                </div>
            </div>
        </div>
    </section>
</div>
