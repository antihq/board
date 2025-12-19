<?php

use App\Models\Team;
use Livewire\Component;

new class extends Component
{
    public Team $team;

    public string $name = '';

    public function create()
    {
        $this->validate([
            'name' => 'required',
        ]);

        $project = $this->team->projects()->create([
            'name' => $this->pull('name'),
        ]);

        $this->redirect("/{$this->team->id}/{$project->id}", navigate: true);
    }
};
?>

<flux:dropdown>
    <flux:navbar.item icon:trailing="chevron-down">Projects</flux:navbar.item>
    <flux:navmenu>
        @unless ($team->projects->isEmpty())
            @foreach ($team->projects as $project)
                <flux:navmenu.item href="/{{ $team->id }}/{{ $project->id }}" wire:navigate>
                    {{ $project->name }}
                </flux:navmenu.item>
            @endforeach

            <flux:menu.separator />
        @endunless

        <flux:modal name="add-project" class="md:w-96">
            <x-slot name="trigger">
                <flux:menu.item icon="plus">New project</flux:menu.item>
            </x-slot>
            <form wire:submit="create">
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">New project</flux:heading>
                        <flux:text class="mt-2">Create a new project for your team.</flux:text>
                    </div>
                    <flux:input
                        wire:model="name"
                        label="Project name"
                        placeholder="Enter project name"
                        autofocus
                        required
                    />
                    <div class="flex">
                        <flux:spacer />
                        <flux:button type="submit" variant="primary" size="sm" color="green">
                            Create project
                        </flux:button>
                    </div>
                </div>
            </form>
        </flux:modal>
    </flux:navmenu>
</flux:dropdown>
