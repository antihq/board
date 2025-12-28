<?php

use App\Models\Section;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public Section $section;

    public $title = '';
    public $color = '';

    public int $page = 1;

    #[On('task.moved')]
    #[On('task.updated')]
    public function refreshTasks()
    {
        unset($this->tasks);
    }

    public function mount()
    {
        $this->title = $this->section->title;
        $this->color = $this->section->color ?? '';
    }

    public function saveSection()
    {
        $this->authorize('update', $this->section);

        $this->validate([
            'title' => 'required|string|max:255',
        ]);

        $this->section->update([
            'title' => $this->title,
            'color' => $this->color ?: null,
        ]);

        Flux::modal('edit-section-' . $this->section->id)->close();
    }

    public function deleteSection()
    {
        $this->authorize('delete', $this->section);

        $this->section->tasks()->update([
            'section_id' => null,
            'section_moved_at' => null,
            'section_moved_by' => null,
        ]);

        $this->section->delete();
        $this->dispatch('task.moved');
        $this->dispatch('section.deleted');

        Flux::modal('delete-section-' . $this->section->id)->close();
    }

    public function loadMore()
    {
        $this->page++;
    }

    #[Computed]
    public function tasks()
    {
        return $this->section
            ->tasks()
            ->with(['creator', 'project', 'tags', 'comments', 'assignees'])
            ->take($this->page * 25)
            ->get();
    }

    #[Computed]
    public function hasMore()
    {
        $total = $this->section->tasks()->count();

        return $total > $this->page * 25;
    }

    public function sortItem($item, $_position)
    {
        $task = $this->section->project->tasks()->findOrFail($item);

        $task->moveToSection($this->section->id, Auth::user());

        $this->dispatch('task.moved');
    }
};
?>

<div>
    <flux:modal :name="'edit-section-' . $section->id" class="w-full max-w-[95vw] lg:max-w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Edit section</flux:heading>
                <flux:text class="mt-2">Change the section title and color.</flux:text>
            </div>
            <flux:input label="Title" wire:model="title" />
            <flux:input label="Color" type="color" wire:model="color" />
            <div class="flex">
                <flux:spacer />
                <flux:button wire:click="saveSection">Save</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:kanban.column {{ $attributes }}>
        <flux:kanban.column.header :heading="$section->title" count="{{ $this->tasks->count() }}">
            <x-slot name="actions">
                <flux:dropdown>
                    <flux:button variant="subtle" icon="ellipsis-horizontal" size="sm" />
                    <flux:menu>
                        <flux:modal.trigger :name="'edit-section-' . $section->id">
                            <flux:menu.item>Edit</flux:menu.item>
                        </flux:modal.trigger>
                        <flux:modal.trigger :name="'delete-section-' . $section->id">
                            <flux:menu.item>Delete</flux:menu.item>
                        </flux:modal.trigger>
                    </flux:menu>
                </flux:dropdown>
            </x-slot>
        </flux:kanban.column.header>
        <flux:kanban.column.cards>
            @island(name: 'section-tasks-{{ $section->id }}')
                <div class="flex flex-col gap-2" wire:sort="sortItem" wire:sort:group="tasks">
                    @forelse ($this->tasks as $task)
                        <div wire:sort:item="{{ $task->id }}" wire:key="task-{{ $task->id }}">
                            <flux:modal.trigger :name="'task-' . $task->id">
                                <x-columns.task-card :task="$task" />
                            </flux:modal.trigger>

                            <flux:modal :name="'task-' . $task->id" class="w-full max-w-[95vw] lg:max-w-150">
                                <livewire:task :task="$task" lazy />
                            </flux:modal>
                        </div>
                    @empty
                        <flux:text class="py-3 text-center" wire:sort:ignore>Drag and drop tasks here</flux:text>
                    @endforelse
                </div>
            @endisland
        </flux:kanban.column.cards>
        @if ($this->hasMore)
            <div wire:intersect.margin.200px="loadMore" wire:island="section-tasks-{{ $section->id }}">
                <flux:text class="pb-2 text-center">Loading...</flux:text>
            </div>
        @endif
    </flux:kanban.column>

    <flux:modal :name="'delete-section-' . $section->id" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete section?</flux:heading>
                <flux:text class="mt-2">
                    You're about to delete this section. Tasks will be moved to pending. This action cannot be reversed.
                </flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="danger" wire:click="deleteSection">Delete section</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
