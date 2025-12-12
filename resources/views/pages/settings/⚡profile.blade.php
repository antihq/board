<?php

use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Profile settings')] class extends Component
{
    public string $name = '';

    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Flux::toast(
            heading: 'Saved',
            text: 'Profile updated successfully.',
            variant: 'success'
        );
    }
}; ?>

<div class="mx-auto max-w-[512px]">
    <flux:link href="/dashboard" class="inline-flex items-center gap-2 text-sm" variant="subtle" inline wire:navigate>
        <flux:icon.chevron-left variant="micro" />
        Back to home
    </flux:link>

    <flux:spacer class="mt-4 lg:mt-8" />

    <form wire:submit="updateProfileInformation">
        <header class="flex items-center gap-3">
            <flux:heading class="text-xl">Profile Settings</flux:heading>
        </header>
        <flux:text class="mt-2">Update your personal information.</flux:text>

        <flux:spacer class="mt-10" />

        <div class="space-y-6">
            <flux:input wire:model="name" label="Name" type="text" required autofocus autocomplete="name" />

            <flux:input wire:model="email" label="Email" type="email" required autocomplete="email" />
        </div>

        <flux:spacer class="mt-8" />

        <flux:button type="submit" variant="primary" color="zinc" class="w-full">Save changes</flux:button>
    </form>
</div>
