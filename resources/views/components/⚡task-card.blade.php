<?php

use App\Models\Task;
use Livewire\Component;
use Livewire\Attributes\Computed;

new class extends Component
{
    public Task $task;

    public bool $showModal = false;

    public string $description = '';

    public bool $isEditingDescription = false;

    public bool $isAddingChecklistItem = false;

    public string $newChecklistItemContent = '';

    public function openModal()
    {
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->isEditingDescription = false;
        $this->isAddingChecklistItem = false;
        $this->newChecklistItemContent = '';
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

    public function startAddingChecklistItem()
    {
        $this->isAddingChecklistItem = true;
        $this->newChecklistItemContent = '';
    }

    public function saveChecklistItem()
    {
        $this->validate([
            'newChecklistItemContent' => 'required|string|max:500',
        ]);

        $this->task->checklistItems()->create([
            'content' => $this->pull('newChecklistItemContent'),
            'completed' => false,
        ]);
    }

    public function cancelAddingChecklistItem()
    {
        $this->isAddingChecklistItem = false;
        $this->newChecklistItemContent = '';
    }

    public function toggleChecklistItem($checklistItemId)
    {
        $checklistItem = $this->task->checklistItems()->findOrFail($checklistItemId);
        $checklistItem->update([
            'completed' => !$checklistItem->completed,
        ]);
    }

    #[Computed]
    public function checklistItems()
    {
        return $this->task->checklistItems()->latest()->get();
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

        <div>
            <flux:heading size="md">Checklist</flux:heading>
            <div class="mt-4 space-y-2">
                @foreach ($this->checklistItems as $item)
                    <flux:field variant="inline">
                        <flux:checkbox
                            wire:change="toggleChecklistItem({{ $item->id }})"
                            :checked="$item->completed"
                        />
                        <flux:label @class(['line-through' => $item->completed])>
                            {{ $item->content }}
                        </flux:label>
                    </flux:field>
                @endforeach

                @if ($this->isAddingChecklistItem)
                    <form wire:submit="saveChecklistItem">
                        <flux:composer
                            wire:model="newChecklistItemContent"
                            rows="1"
                            placeholder="New checklist item..."
                            submit="enter"
                            inline
                        >
                            <x-slot name="actionsTrailing">
                                <flux:button type="button" size="sm" wire:click="cancelAddingChecklistItem">
                                    Cancel
                                </flux:button>
                                <flux:button type="submit" size="sm" variant="primary" color="green">Add</flux:button>
                            </x-slot>
                        </flux:composer>
                    </form>
                @else
                    <flux:button size="xs" wire:click="startAddingChecklistItem">
                        {{ $this->checklistItems->isEmpty() ? 'Add checklist' : 'Add checklist item' }}
                    </flux:button>
                @endif
            </div>
        </div>
    </div>
</flux:modal>
