<?php

use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Async;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Renderless;
use Livewire\Component;

new class extends Component
{
    public Project $project;

    public bool $show = false;

    public string $title = '';

    public function add()
    {
        $this->project->tasks()->create([
            'title' => $this->pull('title'),
            'user_id' => Auth::id(),
        ]);

        $this->show = false;
    }

    #[Renderless, Async]
    public function moveTask($item, $position)
    {
        //
    }

    #[Computed]
    public function openedTasks()
    {
        return $this->project->tasks()->get();
    }
};
?>

@placeholder
    <flux:skeleton.group animate="shimmer">
        <flux:skeleton.line class="mb-2 w-1/4" />
        <flux:skeleton.line />
        <flux:skeleton.line />
        <flux:skeleton.line class="w-3/4" />
    </flux:skeleton.group>
@endplaceholder

<flux:kanban.column :$attributes>
    <flux:kanban.column.header heading="Inbox" count="{{ $this->openedTasks->count() }}" />
    <flux:kanban.column.cards wire:sort="moveTask" wire:sort:group="sections">
        @foreach ($this->openedTasks as $task)
            <livewire:projects.task :$task wire:key="{{ $task->id }}" wire:sort:item="{{ $task->id }}" />
        @endforeach
    </flux:kanban.column.cards>
    <flux:kanban.column.footer>
        <form wire:submit="add" wire:show="show" wire:cloak>
            <flux:kanban.card>
                <div class="flex items-center gap-1">
                    <flux:heading class="flex-1">
                        <input
                            wire:model="title"
                            wire:ref="input"
                            placeholder="New task..."
                            class="w-full outline-none"
                        />
                    </flux:heading>

                    <flux:button type="submit" variant="filled" size="sm" inset="top bottom" class="-me-1.5">
                        Add
                    </flux:button>
                </div>
            </flux:kanban.card>
        </form>
        <flux:button wire:click="$js.reveal" wire:show="!show" variant="subtle" icon="plus" size="sm" align="start">
            New task
        </flux:button>
    </flux:kanban.column.footer>
</flux:kanban.column>

<script>
    this.$js.reveal = () => {
        this.show = true;

        setTimeout(() => {
            this.$refs.input.focus();
        });
    };

    this.$el.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            if (this.show) {
                this.show = false;
            }
        }
    });
</script>
