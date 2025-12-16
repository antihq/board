<?php

use App\Models\Project;
use Livewire\Attributes\Async;
use Livewire\Attributes\Renderless;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Project')] class extends Component
{
    public Project $project;

    public bool $show = false;

    public string $name = '';

    public function mount()
    {
        $this->authorize('view', $this->project);
    }

    public function add()
    {
        $this->project->sections()->create(['name' => $this->pull('name')]);
    }

    #[Renderless, Async]
    public function moveSection($item, $position)
    {
        //
    }
};
?>

<div class="h-full">
    <flux:heading>{{ $project->name }}</flux:heading>

    <flux:spacer class="my-4" />

    <div class="relative h-full">
        <div
            class="absolute inset-y-0 right-0 h-full w-48 bg-gradient-to-l from-white to-transparent dark:from-zinc-900 dark:to-transparent"
        ></div>
        <div class="h-full w-full overflow-x-auto">
            <flux:kanban wire:sort="moveSection">
                <livewire:projects.opened :project="$project" wire:key="opened" wire:sort:item="open" />

                @foreach ($this->project->sections as $section)
                    <livewire:projects.section
                        :section="$section"
                        wire:key="{{ $section->id }}"
                        wire:sort:item="{{ $section->id }}"
                    />
                @endforeach

                <livewire:projects.completed :project="$project" wire:key="completed" wire:sort:item="complete" />

                <flux:kanban.column>
                    <flux:kanban.column.footer class="pt-2">
                        <form wire:submit="add" wire:show="show" wire:cloak>
                            <flux:kanban.card>
                                <div class="flex items-center gap-1">
                                    <flux:heading class="flex-1">
                                        <input
                                            wire:model="name"
                                            wire:ref="input"
                                            placeholder="New section..."
                                            class="w-full outline-none"
                                        />
                                    </flux:heading>

                                    <flux:button
                                        type="submit"
                                        variant="filled"
                                        size="sm"
                                        inset="top bottom"
                                        class="-me-1.5"
                                    >
                                        Add
                                    </flux:button>
                                </div>
                            </flux:kanban.card>
                        </form>
                        <flux:button
                            wire:click="$js.reveal"
                            wire:show="!show"
                            variant="subtle"
                            icon="plus"
                            size="sm"
                            align="start"
                        >
                            New section
                        </flux:button>
                    </flux:kanban.column.footer>
                </flux:kanban.column>
            </flux:kanban>
        </div>
    </div>
</div>

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
