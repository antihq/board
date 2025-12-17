<?php

use App\Models\Task;
use Livewire\Component;

new class extends Component
{
    public Task $task;

    public bool $showModal = false;

    public function openModal()
    {
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
    }
};
?>


<flux:modal class="md:w-96">
    <x-slot name="trigger">
        <flux:kanban.card
            as="button"
            heading="{{ $task->title }}"
            wire:sort:item="{{ $task->id }}"
            class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors"
        />
    </x-slot>
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ $task->title }}</flux:heading>
            <flux:text class="mt-2">Task details and information.</flux:text>
        </div>

        <div class="space-y-4">
            <div class="flex justify-between">
                <flux:text size="sm">Created</flux:text>
                <flux:text size="sm">{{ $task->created_at?->format('M j, Y') }}</flux:text>
            </div>

            @if ($task->completed_at)
                <div class="flex justify-between">
                    <flux:text size="sm">Completed</flux:text>
                    <flux:text size="sm">{{ $task->completed_at->format('M j, Y') }}</flux:text>
                </div>
            @endif

            @if ($task->section)
                <div class="flex justify-between">
                    <flux:text size="sm">Section</flux:text>
                    <flux:text size="sm">{{ $task->section->title }}</flux:text>
                </div>
            @endif
        </div>

        <div class="flex">
            <flux:spacer />
            <flux:modal.close>
                <flux:button variant="ghost">Close</flux:button>
            </flux:modal.close>
        </div>
    </div>
</flux:modal>
