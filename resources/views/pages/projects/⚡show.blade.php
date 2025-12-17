<?php

use App\Models\Project;
use App\Models\Team;
use Livewire\Component;

new class extends Component
{
    public Team $team;

    public Project $project;
};
?>

<div>
    <flux:heading level="1">{{ $project->name }}</flux:heading>

    <flux:spacer class="my-4" />

    <flux:kanban>
        <livewire:columns.inbox :project="$project" />
        <livewire:columns.done :project="$project" />
    </flux:kanban>
</div>
