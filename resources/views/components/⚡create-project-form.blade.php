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

        $project = $this->team->projects()->create([
            'name' => $this->name,
            'user_id' => Auth::id(),
        ]);

        $this->reset('name');

        $this->dispatch('created');
    }
};
?>

<form wire:submit="create">
    <flux:heading class="text-xl">Create Kanban Project</flux:heading>
    <flux:text class="mt-2">Create a new project for your team.</flux:text>

    <flux:spacer class="mt-10" />

    <flux:input label="Project Name" placeholder="Project Name" wire:model="name" />

    <flux:spacer class="mt-8" />

    <flux:button type="submit" variant="primary" color="zinc" class="w-full">Create Project</flux:button>
</form>
