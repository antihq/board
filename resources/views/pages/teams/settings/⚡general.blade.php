<?php

use App\Models\Team;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component {
    public Team $team;

    #[Validate('required|string|max:255')]
    public $name;

    #[Validate('required|string|max:255')]
    public $handle;

    public $editHandle = false;

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

        $originalHandle = $this->team->handle;

        $this->team->update([
            'name' => $this->name,
            'handle' => $this->handle,
        ]);

        $this->reset('editHandle');

        Flux::toast('Changes saved', variant: 'success');

        if ($originalHandle !== $this->handle) {
            return $this->redirectRoute('teams.settings.general', $this->team, navigate: true);
        }
    }
};
?>

<div class="space-y-6">
    <flux:heading level="1" size="lg">Team settings</flux:heading>

    <div class="space-y-8">
        <div class="border-b border-zinc-200 dark:border-zinc-700">
            <flux:navbar class="-mb-px">
                <flux:navbar.item :href="route('teams.settings.general', $team)" :accent="false">
                    General
                </flux:navbar.item>
            </flux:navbar>
        </div>

        <div class="max-w-lg">
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
                    :disabled="!$editHandle"
                    required
                    maxlength="255"
                >
                    <x-slot name="iconTrailing" wire:ignore>
                        @if ($editHandle)
                            <flux:button size="sm" variant="subtle" class="-mr-1" wire:click="$toggle('editHandle')">
                                Cancel
                            </flux:button>
                        @else
                            <flux:button size="sm" variant="subtle" class="-mr-1" wire:click="$toggle('editHandle')">
                                Edit
                            </flux:button>
                        @endif
                    </x-slot>
                </flux:input>

                <div class="flex gap-3">
                    <flux:button type="submit" variant="primary" color="green">Save</flux:button>
                </div>
            </form>
        </div>
    </div>
</div>
