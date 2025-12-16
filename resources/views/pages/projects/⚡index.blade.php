<?php

use App\Models\Project;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Projects')] class extends Component
{
    public function mount()
    {
        //
    }

    public function projects()
    {
        return Project::where('team_id', auth()->user()->currentTeam->id)
            ->with('user', 'team')
            ->orderBy('name')
            ->paginate(10);
    }
};
?>

<div class="h-full">
    <div class="flex items-center justify-between">
        <flux:heading class="text-xl">All projects</flux:heading>

        <flux:modal.trigger name="create-project">
            <flux:button variant="primary" color="zinc" size="sm" icon="plus">New project</flux:button>
        </flux:modal.trigger>
    </div>

    <flux:spacer class="my-4" />

    @if ($this->projects()->count() > 0)
        <flux:table :paginate="$this->projects()" wire:poll>
            <flux:table.head>
                <flux:table.row>
                    <flux:table.cell>Project</flux:table.cell>
                    <flux:table.cell>Created</flux:table.cell>
                    <flux:table.cell></flux:table.cell>
                </flux:table.row>
            </flux:table.head>

            <flux:table.body>
                @foreach ($this->projects() as $project)
                    <flux:table.row :key="$project->id">
                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                <flux:avatar
                                    circle
                                    size="sm"
                                    :name="strtoupper($project->name)"
                                    :color:seed="'project-'.$project->id"
                                />
                                <div>
                                    <flux:link :href="'/projects/'.$project->id" :accent="false" wire:navigate>
                                        {{ $project->name }}
                                    </flux:link>
                                    <div class="text-sm text-zinc-500">
                                        Created {{ $project->created_at->format('M d') }}
                                    </div>
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>{{ $project->created_at->format('M d') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:dropdown>
                                <flux:button variant="subtle" size="sm" icon="ellipsis-horizontal" />
                                <flux:menu>
                                    <flux:menu.item href="/projects/{{ $project->id }}" wire:navigate>
                                        View Project
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.body>
        </flux:table>
    @else
        <div class="flex flex-col items-center justify-center py-12">
            <flux:callout>
                <flux:callout.heading>No projects yet</flux:callout.heading>
                <flux:callout.text>Create your first project to get started with your team.</flux:callout.text>
                <flux:callout.actions>
                    <flux:modal.trigger name="create-project">
                        <flux:button variant="primary" color="zinc" size="sm" icon="plus">New project</flux:button>
                    </flux:modal.trigger>
                </flux:callout.actions>
            </flux:callout>
        </div>
    @endif

    <flux:modal name="create-project" class="md:w-[512px]">
        <livewire:create-project-form @created="$refresh; $flux.modal('create-project').close();" />
    </flux:modal>
</div>
