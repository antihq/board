<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Billing portal')] class extends Component
{
    #[Computed]
    public function team()
    {
        return Auth::user()->currentTeam;
    }

    public function mount()
    {
        abort_unless($this->team->subscribed(), 403);

        return $this->redirect($this->team->billingPortalUrl(url('/dashboard')), navigate: false);
    }
}; ?>

<div>
    <!-- // -->
</div>
