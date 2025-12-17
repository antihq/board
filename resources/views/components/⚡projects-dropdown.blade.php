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

        $this->team->projects()->create([
            'name' => $this->pull('name'),
        ]);
    }
};
?>

<flux:dropdown>
    <flux:navbar.item icon:trailing="chevron-down">Projects</flux:navbar.item>
    <flux:navmenu>
        <flux:modal.trigger name="add-project">
            <flux:menu.item icon="plus">New project</flux:menu.item>

            <flux:modal name="add-project" class="md:w-96">
                <form wire:submit="create">
                    <div class="space-y-6">
                        <div>
                            <flux:heading size="lg">New project</flux:heading>
                            <flux:text class="mt-2">Create a new project for your team.</flux:text>
                        </div>
                        <flux:input wire:model="name" label="Project name" placeholder="Enter project name" required />
                        <div class="flex">
                            <flux:spacer />
                            <flux:button type="submit" variant="primary">Create project</flux:button>
                        </div>
                    </div>
                </form>
            </flux:modal>
        </flux:modal.trigger>
        @unless ($team->projects->isEmpty())
            <flux:menu.separator />
            @foreach ($team->projects as $project)
                <flux:navmenu.item href="/{{ $team->id }}/{{ $project->id }}" wire:navigate>
                    {{ $project->name }}
                </flux:navmenu.item>
            @endforeach
        @endunless
    </flux:navmenu>
</flux:dropdown>
