<?php

use App\Models\Team;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    public Team $team;

    #[Validate('required|string|max:255')]
    public $name;

    #[Validate('required|string|max:255')]
    public $handle;

    public function mount(Team $team)
    {
        $this->authorize('update', $team);
        $this->team = $team;
        $this->name = $team->name;
        $this->handle = $team->handle;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'handle' => ['required', 'string', 'max:255', Rule::unique('teams', 'handle')->ignore($this->team->id)],
        ]);

        $this->team->update([
            'name' => $this->name,
            'handle' => $this->handle,
        ]);

        Flux::toast('Changes saved', variant: 'success');
    }
};
?>

<div>
    <flux:heading level="1" size="xl">General settings</flux:heading>
    <flux:text class="mt-2">Update your team's name and information.</flux:text>

    <div class="mt-8 max-w-lg">
        <form wire:submit="save" class="space-y-6">
            <flux:input
                wire:model="name"
                type="text"
                label="Team name"
                placeholder="Enter team name"
                description:trailing="This name will be visible to all team members."
                required
                maxlength="255"
            />

            <flux:input
                wire:model="handle"
                type="text"
                label="Team handle"
                placeholder="Enter team handle"
                description:trailing="Unique identifier for your team used in urls."
                required
                maxlength="255"
            />

            <div class="flex gap-3">
                <flux:button type="submit" variant="primary" color="green">Save changes</flux:button>

                <flux:button href="{{ route('teams.show', $team) }}" variant="subtle" wire:navigate>
                    Cancel
                </flux:button>
            </div>
        </form>
    </div>
</div>
