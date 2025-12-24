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

    #[Computed]
    public function tasks()
    {
        return $this->section
            ->tasks()
            ->with(['creator', 'project', 'tags', 'comments', 'assignees'])
            ->orderBy('prioritized_at', 'desc')
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    public function sortItem($item, $_position)
    {
        $task = $this->section->project->tasks()->findOrFail($item);

        $updateData = [
            'section_id' => $this->section->id,
            'section_moved_at' => now(),
            'section_moved_by' => Auth::id(),
        ];

        if ($task->completed_at !== null) {
            $updateData = array_merge($updateData, [
                'completed_at' => null,
                'completed_by' => null,
                'reopened_at' => now(),
                'reopened_by' => Auth::id(),
            ]);
        }

        if ($task->closed_at !== null) {
            $updateData = array_merge($updateData, [
                'closed_at' => null,
                'closed_by' => null,
            ]);
        }

        $task->update($updateData);
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
                    </flux:menu>
                </flux:dropdown>
            </x-slot>
        </flux:kanban.column.header>
        <flux:kanban.column.cards wire:sort="sortItem" wire:sort:group="tasks">
            @foreach ($this->tasks as $task)
                <flux:modal class="w-full max-w-[95vw] lg:max-w-150">
                    <x-slot name="trigger">
                        <flux:kanban.card as="button" heading="{{ $task->title }}" wire:sort:item="{{ $task->id }}">
                            <x-slot name="header">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    @if ($task->project)
                                        <flux:text class="font-mono text-xs">
                                            {{ $task->project->handle }}-{{ $task->number }}
                                        </flux:text>
                                    @endif

                                    @unless ($task->tags->isEmpty())
                                        <div class="flex gap-1">
                                            @foreach ($task->tags->take(3) as $tag)
                                                <flux:badge size="sm">{{ $tag->name }}</flux:badge>
                                            @endforeach

                                            @if ($task->tags->count() > 3)
                                                <flux:badge size="sm">+{{ $task->tags->count() - 3 }}</flux:badge>
                                            @endif
                                        </div>
                                    @endunless
                                </div>
                            </x-slot>
                            <x-slot name="footer">
                                <div class="flex w-full items-center justify-between gap-3">
                                    <div class="flex items-center gap-2">
                                        @if ($task->creator)
                                            <flux:avatar
                                                circle
                                                size="xs"
                                                :src="$task->creator->profilePhotoUrl()"
                                                name="{{ $task->creator->name }}"
                                                color="auto"
                                                color:seed="{{ $task->creator->id }}"
                                                tooltip="{{ $task->creator->name }}"
                                            />
                                            <flux:text class="text-xs">
                                                opened
                                                {{ $task->created_at->diffForHumans() }}
                                            </flux:text>
                                        @endif
                                    </div>

                                    <div class="flex items-center gap-3">
                                        @unless ($task->comments->isEmpty())
                                            <flux:text class="inline-flex gap-1 text-xs">
                                                <flux:icon.chat-bubble-bottom-center-text variant="micro" />
                                                {{ $task->comments->count() }}
                                            </flux:text>
                                        @endunless

                                        <flux:avatar.group>
                                            @foreach ($task->assignees->take(3) as $assignee)
                                                <flux:avatar
                                                    circle
                                                    size="xs"
                                                    :src="$assignee->profilePhotoUrl()"
                                                    name="{{ $assignee->name }}"
                                                    color="auto"
                                                    color:seed="{{ $assignee->id }}"
                                                    tooltip="{{ $assignee->name }}"
                                                />
                                            @endforeach

                                            @if ($task->assignees->count() > 3)
                                                <flux:avatar circle size="xs">
                                                    {{ $task->assignees->count() }}+
                                                </flux:avatar>
                                            @endif
                                        </flux:avatar.group>
                                    </div>
                                </div>
                            </x-slot>
                        </flux:kanban.card>
                    </x-slot>

                    <livewire:task :task="$task" wire:key="task-{{ $task->id }}" lazy />
                </flux:modal>
            @endforeach
        </flux:kanban.column.cards>
    </flux:kanban.column>
</div>
