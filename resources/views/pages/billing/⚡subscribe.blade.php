<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Subscribe')] class extends Component
{
    #[Computed]
    public function team()
    {
        return Auth::user()->currentTeam;
    }

    public function mount()
    {
        abort_if($this->team->subscribed(), 403);

        $stripePriceId = config('services.stripe.price_id');

        $this->redirect($this->team->newSubscription('default', $stripePriceId)
            ->checkout([
                'success_url' => '/settings/profile',
                'cancel_url' => '/dashboard',
            ])->asStripeCheckoutSession()->url, navigate: false);
    }
}; ?>

<div>
    <!-- // -->
</div>
