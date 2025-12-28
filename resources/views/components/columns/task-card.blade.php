@props([
    'task',
])

@php
    if ($task->completed_at) {
        $user = $task->completer;
        $action = 'completed';
        $date = $task->completed_at;
    } elseif ($task->closed_at) {
        $user = $task->closer;
        $action = 'closed';
        $date = $task->closed_at;
    } else {
        $user = $task->creator;
        $action = 'opened';
        $date = $task->created_at;
    }
@endphp

<flux:kanban.card as="button" heading="{{ $task->title }}">
    <x-slot name="header">
        <div class="flex flex-wrap items-center gap-1.5">
            @if ($task->project)
                <flux:text class="font-mono text-xs">{{ $task->project->handle }}-{{ $task->number }}</flux:text>
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
                @if ($user)
                    <flux:avatar
                        circle
                        size="xs"
                        :src="$user->profilePhotoUrl()"
                        name="{{ $user->name }}"
                        color="auto"
                        color:seed="{{ $user->id }}"
                        tooltip="{{ $user->name }}"
                    />
                @endif

                <flux:text class="text-xs">
                    {{ $action }}
                    {{ $date->diffForHumans() }}
                </flux:text>
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
                        <flux:avatar circle size="xs">{{ $task->assignees->count() }}+</flux:avatar>
                    @endif
                </flux:avatar.group>
            </div>
        </div>
    </x-slot>
</flux:kanban.card>
