<?php

use App\Models\Board;
use App\Models\Team;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
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
            'name' => $this->pull('name'),
            'user_id' => Auth::id(),
        ]);
    }
}; ?>

<flux:dropdown>
    <flux:navbar.item icon:trailing="chevron-down">Boards</flux:navbar.item>
    <flux:navmenu>
        <flux:modal>
            <x-slot name="trigger">
                <flux:menu.item icon="plus">New board</flux:menu.item>
            </x-slot>

            <form wire:submit="create">
                <flux:heading class="text-xl">Create Kanban Board</flux:heading>
                <flux:text class="mt-2">Create a new board for your team.</flux:text>

                <flux:spacer class="mt-10" />

                <flux:input label="Board Name" placeholder="Project Board" wire:model="name" />

                <flux:spacer class="mt-8" />

                <flux:button type="submit" variant="primary" color="zinc" class="w-full">Create Board</flux:button>
            </form>
        </flux:modal>

        <flux:menu.separator />

        @foreach ($team->boards as $board)
            <flux:navmenu.item href="/boards/{{ $board->id }}" wire:navigate>
                {{ $board->name }}
            </flux:navmenu.item>
        @endforeach
    </flux:navmenu>
</flux:dropdown>
