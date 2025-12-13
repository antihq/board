<?php

use App\Models\Card;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Cards')] class extends Component
{
    public Team $team;

    #[Url(as: 'boards', except: '')]
    public array $selectedBoards = [];

    #[Url(as: 'tags', except: '')]
    public array $selectedTags = [];

    #[Url(as: 'assigned', except: '')]
    public array $selectedAssignees = [];

    #[Url(as: 'created', except: '')]
    public array $selectedCreators = [];

    public function mount()
    {
        $this->team = Auth::user()->currentTeam;
    }

    #[Computed]
    public function boards()
    {
        return $this->team->boards()->orderBy('name')->get();
    }

    #[Computed]
    public function tags()
    {
        return $this->team->tags()->orderBy('name')->get();
    }

    #[Computed]
    public function teamMembers()
    {
        return $this->team->allMembers()->sortBy('name');
    }

    #[Computed]
    public function cards()
    {
        $query = Card::with(['column.board', 'assignees', 'tags', 'user'])
            ->whereHas('column.board.team', function ($query) {
                $query->where('id', $this->team->id);
            });

        // Filter by boards
        if (! empty($this->selectedBoards)) {
            $query->whereHas('column.board', function ($query) {
                $query->whereIn('id', $this->selectedBoards);
            });
        }

        // Filter by tags
        if (! empty($this->selectedTags)) {
            $query->whereHas('tags', function ($query) {
                $query->whereIn('id', $this->selectedTags);
            });
        }

        // Filter by assignees
        if (! empty($this->selectedAssignees)) {
            $query->whereHas('assignees', function ($query) {
                $query->whereIn('users.id', $this->selectedAssignees);
            });
        }

        // Filter by creators
        if (! empty($this->selectedCreators)) {
            $query->whereIn('user_id', $this->selectedCreators);
        }

        return $query->orderBy('updated_at', 'desc')->get();
    }

    public function clearFilters()
    {
        $this->selectedBoards = [];
        $this->selectedTags = [];
        $this->selectedAssignees = [];
        $this->selectedCreators = [];
    }
};
?>

<div class="h-full">
    <flux:heading>All boards</flux:heading>

    <flux:spacer class="my-4" />

    <div class="flex gap-2">
        <!-- Boards Filter -->
        <flux:pillbox multiple placeholder="Board..." wire:model.live="selectedBoards">
            @foreach ($this->boards as $board)
                <flux:pillbox.option value="{{ $board->id }}">{{ $board->name }}</flux:pillbox.option>
            @endforeach
        </flux:pillbox>

        <!-- Tags Filter -->
        <flux:pillbox multiple placeholder="Tagged..." wire:model.live="selectedTags">
            @foreach ($this->tags as $tag)
                <flux:pillbox.option value="{{ $tag->id }}">{{ $tag->name }}</flux:pillbox.option>
            @endforeach
        </flux:pillbox>

        <!-- Assignees Filter -->
        <flux:pillbox multiple placeholder="Assigned to..." wire:model.live="selectedAssignees">
            @foreach ($this->teamMembers as $member)
                <flux:pillbox.option value="{{ $member->id }}">{{ $member->name }}</flux:pillbox.option>
            @endforeach
        </flux:pillbox>

        <!-- Creators Filter -->
        <flux:pillbox multiple placeholder="Added by..." wire:model.live="selectedCreators">
            @foreach ($this->teamMembers as $member)
                <flux:pillbox.option value="{{ $member->id }}">{{ $member->name }}</flux:pillbox.option>
            @endforeach
        </flux:pillbox>

        <!-- Clear Filters Button -->
        @if (! empty($selectedBoards) || ! empty($selectedTags) || ! empty($selectedAssignees) || ! empty($selectedCreators))
            <flux:button variant="ghost" wire:click="clearFilters">Clear All</flux:button>
        @endif
    </div>

    <flux:spacer class="my-4" />

    <div class="grid gap-2 lg:grid-cols-3">
        @foreach ($this->cards as $card)
            <livewire:boards.card :card="$card" :key="'card-' . $card->id" class="rounded-lg shadow-xs" />
        @endforeach
    </div>
</div>
