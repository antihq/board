<?php

use App\Models\Task;
use Livewire\Component;

new class extends Component
{
    public Task $task;

    public bool $showModal = false;

    public string $description = '';

    public bool $isEditingDescription = false;

    public function openModal()
    {
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->isEditingDescription = false;
    }

    public function editDescription()
    {
        $this->description = $this->task->description ?? '';
        $this->isEditingDescription = true;
    }

    public function saveDescription()
    {
        $this->validate([
            'description' => 'nullable|string|max:5000',
        ]);

        $this->task->update([
            'description' => $this->description,
        ]);

        $this->isEditingDescription = false;
    }

    public function cancelEdit()
    {
        $this->isEditingDescription = false;
        $this->description = '';
    }
};
?>

<flux:modal class="max-w-[95vw] md:w-[600px]">
    <x-slot name="trigger">
        <flux:kanban.card as="button" heading="{{ $task->title }}" wire:sort:item="{{ $task->id }}" />
    </x-slot>

    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ $task->title }}</flux:heading>
        </div>

        @if ($this->isEditingDescription)
            <form wire:submit="saveDescription">
                <div class="space-y-4">
                    <div>
                        <flux:composer
                            wire:model="description"
                            rows="6"
                            max-rows="12"
                            label="Task Description"
                            placeholder="Add a detailed description..."
                            submit="enter"
                        >
                            <x-slot name="input">
                                <flux:editor
                                    variant="borderless"
                                    toolbar="heading | bold italic | bullet ordered | link"
                                    placeholder="Add a detailed description..."
                                />
                            </x-slot>
                            <x-slot name="actionsLeading"></x-slot>
                            <x-slot name="actionsTrailing">
                                <flux:button type="button" size="sm" wire:click="cancelEdit">Cancel</flux:button>
                                <flux:button type="submit" size="sm" variant="primary" color="green">Save</flux:button>
                            </x-slot>
                        </flux:composer>
                    </div>
                </div>
            </form>
        @else
            <div class="space-y-4">
                @if ($task->description)
                    <div class="prose prose-sm prose-zinc dark:prose-invert max-w-none">
                        {!! $task->description !!}
                    </div>
                    <flux:button size="xs" wire:click="editDescription">Edit description</flux:button>
                @else
                    <flux:button size="xs" wire:click="editDescription">Add description</flux:button>
                @endif
            </div>
        @endif
    </div>
</flux:modal>
