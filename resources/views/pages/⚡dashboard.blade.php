<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Home')] class extends Component {
    public function mount()
    {
        return $this->redirect(
            '/' .
                Auth::user()
                    ->teams()
                    ->first()->id,
        );
    }
};
?>

<div>
    <!--  -->
</div>
