<?php

use App\Models\Board;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Boards')] class extends Component
{
    #[Computed]
    public function boards()
    {
        return Board::where('team_id', auth()->user()->currentTeam->id)
            ->latest()
            ->paginate(10);
    }
};
?>

<div>
    @if ($this->boards->count() > 0)
        <header class="flex items-center">
            <flux:heading class="text-xl">All boards</flux:heading>
            <flux:spacer />
            <flux:modal.trigger name="create-board">
                <flux:button variant="primary" color="zinc" size="sm" icon="plus">New board</flux:button>
            </flux:modal.trigger>
        </header>

        <flux:separator class="mt-6" />

        <flux:table :paginate="$this->boards" wire:poll>
            <flux:table.rows>
                @foreach ($this->boards as $board)
                    <flux:table.row :key="$board->id">
                        <flux:table.cell class="w-full">
                            <div class="flex items-center gap-3">
                                <flux:avatar
                                    :name="strtoupper($board->name)"
                                    size="xs"
                                    color="auto"
                                    initials:single
                                    :color:seed="'board-'.$board->id"
                                />
                                <flux:link :href="'/boards/'.$board->id" :accent="false" wire:navigate>
                                    {{ $board->name }}
                                </flux:link>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell align="end" class="text-xs">
                            {{ $board->created_at->format('M d') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @else
        <flux:callout icon="document-text" inline>
            <flux:callout.heading>No boards yet</flux:callout.heading>
            <flux:callout.text>Create your first board to get started with your team.</flux:callout.text>
            <x-slot name="actions">
                <flux:modal.trigger name="create-board">
                    <flux:button variant="primary" color="zinc" size="sm" icon="plus">New board</flux:button>
                </flux:modal.trigger>
            </x-slot>
        </flux:callout>
    @endif

    <flux:modal name="create-board" class="md:w-[512px]">
        <livewire:create-board-form @created="$refresh; $flux.modal('create-board').close();" />
    </flux:modal>
</div>
