<?php

use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public Team $team;

    #[Computed]
    public function savedTasks()
    {
        return Auth::user()
            ->savedTasks()
            ->wherePivot('team_id', $this->team->id)
            ->with(['creator', 'completer', 'closer', 'project', 'tags', 'comments', 'assignees'])
            ->latest('saved_tasks.created_at')
            ->paginate(15);
    }

    public function mount()
    {
        $this->authorize('view', $this->team);
    }
};
?>

<div class="space-y-4">
    <div class="flex items-center justify-between">
        <flux:heading level="1" size="lg">Saved Tasks</flux:heading>
    </div>

    @if ($this->savedTasks->isEmpty())
        <div class="py-12 text-center text-zinc-500">
            <p>You haven't saved any tasks yet.</p>
            <p class="mt-1 text-sm">Click the bookmark icon on a task to save it for quick access.</p>
        </div>
    @else
        <div class="border-t border-zinc-800/5 dark:border-white/10">
            @foreach ($this->savedTasks as $task)
                <div wire:key="task-{{ $task->id }}">
                    <flux:modal.trigger name="task-{{ $task->id }}">
                        <x-list-item as="button">
                            <x-slot name="heading">
                                <div class="flex w-full justify-between">
                                    <div class="space-y-2">
                                        <div class="font-medium">{{ $task->title }}</div>
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            @unless ($task->tags->isEmpty())
                                                <div class="flex items-center gap-1">
                                                    @foreach ($task->tags->take(3) as $tag)
                                                        <flux:badge size="sm">{{ $tag->name }}</flux:badge>
                                                    @endforeach

                                                    @if ($task->tags->count() > 3)
                                                        <flux:badge size="sm">
                                                            +{{ $task->tags->count() - 3 }}
                                                        </flux:badge>
                                                    @endif
                                                </div>

                                                <flux:text class="text-xs">·</flux:text>
                                            @endunless

                                            @if ($task->project)
                                                <flux:text class="font-mono text-xs">
                                                    {{ $task->project->handle }}-{{ $task->number }}
                                                </flux:text>

                                                <flux:text class="text-xs">·</flux:text>
                                            @endif

                                            @unless ($task->completed_at || $task->closed_at)
                                                <div class="flex items-center gap-2">
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
                                                </div>
                                            @endunless

                                            @if ($task->completed_at && $task->completer)
                                                <flux:text class="text-xs">·</flux:text>
                                                <div class="flex items-center gap-2">
                                                    <flux:avatar
                                                        circle
                                                        size="xs"
                                                        :src="$task->completer->profilePhotoUrl()"
                                                        name="{{ $task->completer->name }}"
                                                        color="auto"
                                                        color:seed="{{ $task->completer->id }}"
                                                        tooltip="{{ $task->completer->name }}"
                                                    />
                                                    <flux:text class="text-xs">
                                                        completed
                                                        {{ $task->completed_at->diffForHumans() }}
                                                    </flux:text>
                                                </div>
                                            @endif

                                            @if ($task->closed_at && $task->closer)
                                                <flux:text class="text-xs">·</flux:text>
                                                <div class="flex items-center gap-2">
                                                    <flux:avatar
                                                        circle
                                                        size="xs"
                                                        :src="$task->closer->profilePhotoUrl()"
                                                        name="{{ $task->closer->name }}"
                                                        color="auto"
                                                        color:seed="{{ $task->closer->id }}"
                                                        tooltip="{{ $task->closer->name }}"
                                                    />
                                                    <flux:text class="text-xs">
                                                        closed
                                                        {{ $task->closed_at->diffForHumans() }}
                                                    </flux:text>
                                                </div>
                                            @endif
                                        </div>
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
                        </x-list-item>
                    </flux:modal.trigger>

                    <flux:modal name="task-{{ $task->id }}" class="w-full max-w-[95vw] lg:max-w-216">
                        <livewire:task :task="$task" wire:key="task-{{ $task->id }}" lazy />
                    </flux:modal>

                    @unless ($loop->last)
                        <flux:separator variant="subtle" />
                    @endunless
                </div>
            @endforeach
        </div>

        <flux:pagination :paginator="$this->savedTasks" class="mt-4" />
    @endif
</div>
