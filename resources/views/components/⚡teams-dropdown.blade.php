<?php

use App\Models\Team;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public ?Team $currentTeam;

    public Collection $teams;

    public ?int $selectedTeamId;

    #[Computed]
    public function user()
    {
        return Auth::user();
    }

    public function mount()
    {
        $this->currentTeam = $this->user->currentTeam;
        $this->teams = $this->user->allTeams();
        $this->selectedTeamId = $this->currentTeam?->id;
    }

    public function switchTeam(Team $team)
    {
        $this->authorize('switch', $team);
        $this->user->switchTeam($team);
        $this->redirect('/dashboard', navigate: true);
    }

    public function updatedSelectedTeamId(Team $team)
    {
        $this->switchTeam($team);
    }
}; ?>

<flux:button.group>
    <flux:button href="/dashboard" variant="subtle" size="sm" wire:navigate>
        <flux:avatar size="xs" :name="$currentTeam?->name" color="auto" initials:single class="-ml-2" />
        {{ $currentTeam->name }}
    </flux:button>
    <flux:dropdown position="top" align="start">
        <flux:button icon="chevron-up-down" variant="subtle" size="sm" square></flux:button>
        <flux:menu>
            <flux:menu.radio.group wire:model.live="selectedTeamId">
                @foreach ($teams as $team)
                    <flux:menu.radio :value="$team->id">
                        {{ $team->name }}
                    </flux:menu.radio>
                @endforeach
            </flux:menu.radio.group>
            <flux:menu.separator />
            <flux:menu.item href="/teams/create" icon="plus" wire:navigate>New team</flux:menu.item>
        </flux:menu>
    </flux:dropdown>
</flux:button.group>
