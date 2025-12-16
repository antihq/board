<?php

use App\Models\Project;
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

        $project = $this->team->projects()->create([
            'name' => $this->pull('name'),
            'user_id' => Auth::id(),
        ]);

        return $this->redirect("/projects/{$project->id}");
    }
}; ?>

<flux:dropdown>
    <flux:navbar.item icon:trailing="chevron-down">Projects</flux:navbar.item>
    <flux:navmenu>
        <flux:modal>
            <x-slot name="trigger">
                <flux:menu.item icon="plus">New project</flux:menu.item>
            </x-slot>

            <form wire:submit="create">
                <flux:heading class="text-xl">Create Kanban Project</flux:heading>
                <flux:text class="mt-2">Create a new project for your team.</flux:text>

                <flux:spacer class="mt-10" />

                <flux:input label="Project Name" placeholder="Project Name" wire:model="name" />

                <flux:spacer class="mt-8" />

                <flux:button type="submit" variant="primary" color="zinc" class="w-full">Create Project</flux:button>
            </form>
        </flux:modal>

        <flux:menu.separator />

        @foreach ($team->projects as $project)
            <flux:navmenu.item href="/projects/{{ $project->id }}" wire:navigate>
                {{ $project->name }}
            </flux:navmenu.item>
        @endforeach
    </flux:navmenu>
</flux:dropdown>
