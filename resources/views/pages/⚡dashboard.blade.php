<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Home')] class extends Component
{
    public function mount()
    {
        return $this->redirectRoute(
            'teams.show',
            Auth::user()
                ->teams()
                ->first(),
        );
    }
};
?>

<div>
    <!--  -->
</div>
