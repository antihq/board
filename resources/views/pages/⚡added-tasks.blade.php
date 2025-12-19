<?php

use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public Team $team;

    #[Computed]
    public function user(): User
    {
        return Auth::user();
    }

    #[Computed]
    public function tasks()
    {
        return $this->team
            ->tasks()
            ->where('user_id', Auth::id())
            ->with(['project', 'team'])
            ->orderBy('updated_at', 'desc')
            ->get();
    }
};
?>

<div>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <flux:heading level="1" size="lg">Tasks added by me</flux:heading>
        </div>

        @if ($this->tasks->isEmpty())
            <div class="py-12 text-center text-gray-500">
                <p>You haven't created any tasks yet.</p>
            </div>
        @else
            <div class="border-t border-zinc-800/5 dark:border-white/10">
                @foreach ($this->tasks as $task)
                    <flux:modal class="w-full max-w-[95vw] lg:max-w-150">
                        <x-slot name="trigger">
                            <x-list-item as="button" heading="{{ $task->title }}" wire:sort:item="{{ $task->id }}" />
                            <flux:separator variant="subtle" />
                        </x-slot>

                        <livewire:task :task="$task" wire:key="task-{{ $task->id }}" lazy />
                    </flux:modal>
                @endforeach
            </div>
        @endif
    </div>
</div>
