<?php

use App\Models\Section;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
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

    public function save()
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

    public function delete()
    {
        $this->authorize('delete', $this->section);

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

    #[Computed]
    public function colorClass()
    {
        return match ($this->color) {
            'red' => 'bg-red-50 dark:bg-red-950',
            'orange' => 'bg-orange-50 dark:bg-orange-950',
            'amber' => 'bg-amber-50 dark:bg-amber-950',
            'yellow' => 'bg-yellow-50 dark:bg-yellow-950',
            'lime' => 'bg-lime-50 dark:bg-lime-950',
            'green' => 'bg-green-50 dark:bg-green-950',
            'emerald' => 'bg-emerald-50 dark:bg-emerald-950',
            'teal' => 'bg-teal-50 dark:bg-teal-950',
            'cyan' => 'bg-cyan-50 dark:bg-cyan-950',
            'sky' => 'bg-sky-50 dark:bg-sky-950',
            'blue' => 'bg-blue-50 dark:bg-blue-950',
            'indigo' => 'bg-indigo-50 dark:bg-indigo-950',
            'violet' => 'bg-violet-50 dark:bg-violet-950',
            'purple' => 'bg-purple-50 dark:bg-purple-950',
            'fuchsia' => 'bg-fuchsia-50 dark:bg-fuchsia-950',
            'pink' => 'bg-pink-50 dark:bg-pink-950',
            'rose' => 'bg-rose-50 dark:bg-rose-950',
            default => '',
        };
    }

    public function sortItem($item, $_position)
    {
        $task = $this->section->project->tasks()->findOrFail($item);

        if ($task->isInSection($this->section)) {
            Flux::toast(heading: 'Cannot move', text: 'Card is already in this section', variant: 'warning');

            return;
        }

        $task->moveToSection($this->section, Auth::user());

        $this->dispatch('task.moved');
    }
};
?>

<div>
    <flux:kanban.column {{ $attributes->class($this->colorClass) }}>
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
            @island(name: 'section-tasks-{{ $section->id }}', always: true)
                <div class="flex flex-col gap-2" wire:sort="sortItem" wire:sort:group="tasks">
                    @forelse ($this->tasks as $task)
                        <div wire:sort:item="{{ $task->id }}" wire:key="task-{{ $task->id }}">
                            <flux:modal.trigger :name="'task-' . $task->id">
                                <x-columns.task-card :task="$task" />
                            </flux:modal.trigger>

                            <flux:modal
                                :name="'task-' . $task->id"
                                @close="$refresh"
                                class="w-full max-w-[95vw] lg:max-w-216"
                            >
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

    <flux:modal :name="'edit-section-' . $section->id" class="w-full max-w-[95vw] lg:max-w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Edit section</flux:heading>
                <flux:text class="mt-2">Change the section title and color.</flux:text>
            </div>
            <form wire:submit="save" class="space-y-6">
                <flux:input label="Title" wire:model="title" />
                <flux:select label="Color" wire:model="color" variant="listbox" placeholder="Choose a color...">
                    <flux:select.option value="">None</flux:select.option>
                    <flux:select.option value="red">
                        <div class="flex items-center gap-2">
                            <div class="h-4 w-4 rounded-full bg-red-500 dark:bg-red-600"></div>
                            Red
                        </div>
                    </flux:select.option>
                    <flux:select.option value="orange">
                        <div class="flex items-center gap-2">
                            <div class="h-4 w-4 rounded-full bg-orange-500 dark:bg-orange-600"></div>
                            Orange
                        </div>
                    </flux:select.option>
                    <flux:select.option value="amber">
                        <div class="flex items-center gap-2">
                            <div class="h-4 w-4 rounded-full bg-amber-500 dark:bg-amber-600"></div>
                            Amber
                        </div>
                    </flux:select.option>
                    <flux:select.option value="yellow">
                        <div class="flex items-center gap-2">
                            <div class="h-4 w-4 rounded-full bg-yellow-500 dark:bg-yellow-600"></div>
                            Yellow
                        </div>
                    </flux:select.option>
                    <flux:select.option value="lime">
                        <div class="flex items-center gap-2">
                            <div class="h-4 w-4 rounded-full bg-lime-500 dark:bg-lime-600"></div>
                            Lime
                        </div>
                    </flux:select.option>
                    <flux:select.option value="green">
                        <div class="flex items-center gap-2">
                            <div class="h-4 w-4 rounded-full bg-green-500 dark:bg-green-600"></div>
                            Green
                        </div>
                    </flux:select.option>
                    <flux:select.option value="emerald">
                        <div class="flex items-center gap-2">
                            <div class="h-4 w-4 rounded-full bg-emerald-500 dark:bg-emerald-600"></div>
                            Emerald
                        </div>
                    </flux:select.option>
                    <flux:select.option value="teal">
                        <div class="flex items-center gap-2">
                            <div class="h-4 w-4 rounded-full bg-teal-500 dark:bg-teal-600"></div>
                            Teal
                        </div>
                    </flux:select.option>
                    <flux:select.option value="cyan">
                        <div class="flex items-center gap-2">
                            <div class="h-4 w-4 rounded-full bg-cyan-500 dark:bg-cyan-600"></div>
                            Cyan
                        </div>
                    </flux:select.option>
                    <flux:select.option value="sky">
                        <div class="flex items-center gap-2">
                            <div class="h-4 w-4 rounded-full bg-sky-500 dark:bg-sky-600"></div>
                            Sky
                        </div>
                    </flux:select.option>
                    <flux:select.option value="blue">
                        <div class="flex items-center gap-2">
                            <div class="h-4 w-4 rounded-full bg-blue-500 dark:bg-blue-600"></div>
                            Blue
                        </div>
                    </flux:select.option>
                    <flux:select.option value="indigo">
                        <div class="flex items-center gap-2">
                            <div class="h-4 w-4 rounded-full bg-indigo-500 dark:bg-indigo-600"></div>
                            Indigo
                        </div>
                    </flux:select.option>
                    <flux:select.option value="violet">
                        <div class="flex items-center gap-2">
                            <div class="h-4 w-4 rounded-full bg-violet-500 dark:bg-violet-600"></div>
                            Violet
                        </div>
                    </flux:select.option>
                    <flux:select.option value="purple">
                        <div class="flex items-center gap-2">
                            <div class="h-4 w-4 rounded-full bg-purple-500 dark:bg-purple-600"></div>
                            Purple
                        </div>
                    </flux:select.option>
                    <flux:select.option value="fuchsia">
                        <div class="flex items-center gap-2">
                            <div class="h-4 w-4 rounded-full bg-fuchsia-500 dark:bg-fuchsia-600"></div>
                            Fuchsia
                        </div>
                    </flux:select.option>
                    <flux:select.option value="pink">
                        <div class="flex items-center gap-2">
                            <div class="h-4 w-4 rounded-full bg-pink-500 dark:bg-pink-600"></div>
                            Pink
                        </div>
                    </flux:select.option>
                    <flux:select.option value="rose">
                        <div class="flex items-center gap-2">
                            <div class="h-4 w-4 rounded-full bg-rose-500 dark:bg-rose-600"></div>
                            Rose
                        </div>
                    </flux:select.option>
                </flux:select>
                <div class="flex">
                    <flux:spacer />
                    <flux:button type="submit" variant="primary">Save</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

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
                <flux:button type="submit" variant="danger" wire:click="delete">Delete section</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
