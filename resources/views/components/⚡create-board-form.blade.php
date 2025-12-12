<?php

use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public Team $team;

    public string $name = '';

    public function mount()
    {
        $this->team = Auth::user()->currentTeam;
    }

    public function create()
    {
        $this->validate([
            'name' => 'required|string|max:255',
        ]);

        $board = $this->team->boards()->create([
            'name' => $this->name,
            'user_id' => Auth::id(),
        ]);

        $board->createDefaultColumns();

        $this->reset('name');

        $this->dispatch('created');
    }
};
?>

<form wire:submit="create">
    <flux:heading class="text-xl">Create Kanban Board</flux:heading>
    <flux:text class="mt-2">Create a new board for your team.</flux:text>

    <flux:spacer class="mt-10" />

    <flux:input label="Board Name" placeholder="Project Board" wire:model="name" />

    <flux:spacer class="mt-8" />

    <flux:button type="submit" variant="primary" color="zinc" class="w-full">Create Board</flux:button>
</form>
