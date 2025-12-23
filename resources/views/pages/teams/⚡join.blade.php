<?php

use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::auth', ['dark' => false]), Title('Join')] class extends Component
{
    public Team $team;
    public string $invitationCode;
    public string $name = '';
    public string $email = '';
    public bool $displayingJoinForm = true;

    public function mount(Team $team, string $invitation_code)
    {
        $this->team = $team;
        $this->invitationCode = $invitation_code;

        if ($team->invitation_code !== $invitation_code) {
            abort(403, 'Invalid invitation code');
        }

        if ($team->invitation_code_uses_count >= $team->invitation_code_max_uses) {
            abort(403, 'Invitation link has reached its maximum number of uses');
        }

        if (
            Auth::check() &&
            Auth::user()
                ->joinedTeams()
                ->where('teams.id', $this->team->id)
                ->exists()
        ) {
            return $this->redirect(route('dashboard'), navigate: true);
        }

        if (Auth::check()) {
            $this->displayingJoinForm = false;
        }
    }

    public function join()
    {
        if (Auth::check()) {
            return $this->joinAuthenticated();
        }

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $user = User::firstWhere('email', $this->email);

        if (! $user) {
            $user = $this->createUser();
        }

        $user->sendOneTimePassword();

        $this->displayingJoinForm = false;
    }

    protected function joinAuthenticated()
    {
        $updated = $this->team
            ->where('id', $this->team->id)
            ->where('invitation_code_uses_count', '<', $this->team->invitation_code_max_uses)
            ->increment('invitation_code_uses_count');

        if (! $updated) {
            abort(403, 'Invitation link has reached its maximum number of uses');
        }

        Auth::user()
            ->joinedTeams()
            ->attach($this->team->id, ['role' => 'member']);

        return $this->redirect(route('teams.show', ['team' => $this->team]), navigate: true);
    }

    protected function createUser(): User
    {
        return DB::transaction(function () {
            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
            ]);

            event(new Registered($user));

            $this->createTeam($user);

            return $user;
        });
    }

    protected function createTeam(User $user): void
    {
        $teamName = explode(' ', $user->name, 2)[0] . "'s Team";
        $handle = Team::generateUniqueHandle($teamName);

        $user->teams()->save(
            Team::forceCreate([
                'user_id' => $user->id,
                'name' => $teamName,
                'handle' => $handle,
                'personal' => true,
                'invitation_code' => Str::random(8),
            ]),
        );
    }
};
?>

<div class="isolate mx-auto flex min-h-dvh max-w-7xl items-center justify-center gap-12 max-lg:flex-col">
    <div class="w-full max-w-md">
        @if ($displayingJoinForm)
            <div class="rounded-xl bg-white shadow-md ring-1 ring-black/5">
                <div class="p-7 sm:p-11">
                    <form wire:submit="join" class="space-y-8">
                        <div>
                            <flux:heading level="1" class="text-base/6! font-medium">
                                Join {{ $team->name }}
                            </flux:heading>
                            <flux:text class="mt-1 text-sm/5">
                                Enter your details to join this team and start collaborating
                            </flux:text>
                        </div>

                        <flux:input
                            wire:model="name"
                            label="Name"
                            type="text"
                            required
                            autofocus
                            autocomplete="name"
                            placeholder="Full name"
                        />

                        <flux:input
                            wire:model="email"
                            label="Email address"
                            type="email"
                            required
                            autocomplete="email"
                            placeholder="email@example.com"
                        />

                        <flux:button variant="primary" color="green" type="submit" class="w-full text-base!">
                            Join team
                        </flux:button>
                    </form>
                </div>
            </div>
        @elseif (Auth::check())
            <div class="rounded-xl bg-white shadow-md ring-1 ring-black/5">
                <div class="p-7 sm:p-11">
                    <div class="space-y-8">
                        <div>
                            <flux:heading level="1" class="text-base/6! font-medium">
                                Join {{ $team->name }}
                            </flux:heading>
                            <flux:text class="mt-1 text-sm/5">
                                Click the button below to join this team and start collaborating
                            </flux:text>
                        </div>

                        <form wire:submit="join" class="space-y-8">
                            <flux:button variant="primary" color="green" type="submit" class="w-full text-base!">
                                Join team
                            </flux:button>
                        </form>
                    </div>
                </div>
            </div>
        @else
            <div class="rounded-xl bg-white shadow-md ring-1 ring-black/5">
                <div class="p-7 sm:p-11">
                    <livewire:join-one-time-pin :email="$email" :team="$team" :invitation-code="$invitationCode" />
                </div>
            </div>
        @endif
    </div>
</div>
