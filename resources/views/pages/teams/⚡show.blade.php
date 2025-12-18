<?php

use App\Models\Team;
use Livewire\Component;

new class extends Component {
    public Team $team;

    public function mount()
    {
        $this->authorize('view', $this->team);
    }
};
?>

<div>
    {{-- Always remember that you are absolutely unique. Just like everyone else. - Margaret Mead --}}
</div>
