<?php

use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::auth', ['dark' => false]), Title('Join')] class extends Component
{
    public Team $team;
    public string $invitationCode;

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
    }

    public function join()
    {
        if (! Auth::check()) {
            return $this->redirect(route('login'), navigate: true);
        }

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
};
?>

<div class="isolate mx-auto flex min-h-dvh max-w-7xl items-center justify-center gap-12 max-lg:flex-col">
    <div class="w-full max-w-md">
        <div class="rounded-xl bg-white shadow-md ring-1 ring-black/5">
            <div class="p-7 sm:p-11">
                <form wire:submit="join" class="space-y-8">
                    <div>
                        <flux:heading level="1" class="text-base/6! font-medium">Join {{ $team->name }}</flux:heading>
                        <flux:text class="mt-1 text-sm/5">
                            Click the button below to join this team and start collaborating
                        </flux:text>
                    </div>

                    <flux:button variant="primary" color="green" type="submit" class="w-full text-base!">
                        Join team
                    </flux:button>

                    @if (! Auth::check())
                        <flux:separator text="or" />

                        <flux:button href="/login" class="w-full text-base!" wire:navigate>Sign in to join</flux:button>
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>
