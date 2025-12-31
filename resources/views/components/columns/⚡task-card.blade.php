<?php

use App\Models\Task;
use Livewire\Component;

new class extends Component {
    public Task $task;
};
?>

<div {{ $attributes }}>
    <flux:modal.trigger :name="'task-' . $task->id">
        <flux:kanban.card as="button" :heading="$this->task->title">
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
                    <div class="flex items-center gap-3">
                        @if ($task->savers->contains(auth()->id()))
                            <flux:tooltip content="Saved">
                                <flux:text class="text-xs">
                                    <flux:icon name="bookmark" variant="micro" />
                                </flux:text>
                            </flux:tooltip>
                        @endif

                        @if ($task->subscribers->contains(auth()->id()))
                            <flux:tooltip content="Subscribed">
                                <flux:text class="text-xs">
                                    <flux:icon name="eye" variant="micro" />
                                </flux:text>
                            </flux:tooltip>
                        @endif

                        @if ($task->description)
                            <flux:tooltip content="Has description">
                                <flux:text class="text-xs">
                                    <flux:icon name="bars-3-bottom-left" variant="micro" />
                                </flux:text>
                            </flux:tooltip>
                        @endif

                        @unless ($task->comments->isEmpty())
                            <flux:tooltip
                                content="{{ $task->comments->count() }} {{ Str::plural('comment', $task->comments->count()) }}"
                            >
                                <flux:text class="flex items-center gap-1 text-xs">
                                    <flux:icon name="chat-bubble-left-right" variant="micro" />
                                    {{ $task->comments->count() }}
                                </flux:text>
                            </flux:tooltip>
                        @endunless

                        @unless ($task->images->isEmpty())
                            <flux:tooltip
                                content="{{ $task->images->count() }} {{ Str::plural('attachment', $task->images->count()) }}"
                            >
                                <flux:text class="flex items-center gap-1 text-xs">
                                    <flux:icon name="paper-clip" variant="micro" />
                                    {{ $task->images->count() }}
                                </flux:text>
                            </flux:tooltip>
                        @endunless

                        @unless ($task->checklistItems->isEmpty())
                            <flux:tooltip
                                content="{{ $task->checklistItems->where('completed', true)->count() }} of {{ $task->checklistItems->count() }} items completed"
                            >
                                <flux:text class="flex items-center gap-1 text-xs">
                                    <flux:icon name="clipboard-document-check" variant="micro" />
                                    {{ $task->checklistItems->where('completed', true)->count() }}/{{ $task->checklistItems->count() }}
                                </flux:text>
                            </flux:tooltip>
                        @endunless
                    </div>

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
                            <flux:avatar circle size="xs">{{ $task->assignees->count() }}+</flux:avatar>
                        @endif
                    </flux:avatar.group>
                </div>
            </x-slot>
        </flux:kanban.card>
    </flux:modal.trigger>

    <flux:modal :name="'task-' . $task->id" class="w-full max-w-[95vw] lg:max-w-216" @close="$refresh">
        <livewire:task :task="$task" lazy />
    </flux:modal>
</div>
