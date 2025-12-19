<?php

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public Team $team;
    public $selectedTeamId;

    public function mount(): void
    {
        $this->selectedTeamId = $this->team->id;
    }

    #[Computed]
    public function teams()
    {
        $ownedTeams = $this->user->teams;
        $joinedTeams = $this->user->joinedTeams;

        return $ownedTeams->merge($joinedTeams)->unique('id');
    }

    #[Computed]
    public function user(): User
    {
        return Auth::user();
    }

    public function updatedSelectedTeamId($teamId): void
    {
        abort_if(! $this->teams->contains('id', $teamId), 404);

        $this->redirectRoute('teams.show', ['team' => $teamId], navigate: true);
    }
};
?>

<flux:button.group>
    <flux:button href="/{{ $team->id }}" variant="subtle" size="sm" wire:navigate>
        {{ $team->name }}
    </flux:button>
    <flux:dropdown position="top" align="start">
        <flux:button icon="chevron-up-down" variant="subtle" size="sm" square></flux:button>
        <flux:menu>
            <flux:menu.radio.group wire:model.live="selectedTeamId">
                @foreach ($this->teams as $team)
                    <flux:menu.radio :value="$team->id">
                        {{ $team->name }}
                    </flux:menu.radio>
                @endforeach
            </flux:menu.radio.group>
        </flux:menu>
    </flux:dropdown>
</flux:button.group>
