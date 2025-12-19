<?php

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public Team $team;

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
};
?>

<flux:dropdown>
    <flux:navbar.item icon:trailing="chevron-down">{{ $team->name }}</flux:navbar.item>
    <flux:navmenu>
        @foreach ($this->teams as $team)
            <flux:navmenu.item href="/{{ $team->id }}" wire:navigate>
                {{ $team->name }}
            </flux:navmenu.item>
        @endforeach
    </flux:navmenu>
</flux:dropdown>
