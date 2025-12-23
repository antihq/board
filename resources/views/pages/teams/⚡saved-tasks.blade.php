<?php

use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public Team $team;

    #[Computed]
    public function savedTasks()
    {
        return Auth::user()
            ->savedTasks()
            ->wherePivot('team_id', $this->team->id)
            ->with(['project', 'assignees', 'tags'])
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
        <div class="py-12 text-center text-gray-500">
            <p>You haven't saved any tasks yet.</p>
            <p class="mt-1 text-sm">Click the bookmark icon on a task to save it for quick access.</p>
        </div>
    @else
        <div class="border-t border-zinc-800/5 dark:border-white/10">
            @foreach ($this->savedTasks as $task)
                <div wire:key="task-{{ $task->id }}">
                    <flux:modal.trigger name="task-{{ $task->id }}">
                        <x-list-item as="button" heading="{{ $task->title }}" />
                    </flux:modal.trigger>

                    <flux:modal name="task-{{ $task->id }}" class="w-full max-w-[95vw] lg:max-w-150">
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
