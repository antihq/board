<?php

use App\Models\Team;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Profile settings')] class extends Component
{
    use WithFileUploads;

    public Team $team;

    public string $name = '';

    public string $email = '';

    public $photo;

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

        $this->validate([
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],

            'photo' => ['nullable', 'image', 'max:10240'],
        ]);

        $user->fill([
            'name' => $this->name,
            'email' => $this->email,
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($this->photo) {
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }
            $path = $this->photo->store('profile-photos', 'public');
            $user->profile_photo_path = $path;
        }

        $user->save();

        Flux::toast(heading: 'Saved', text: 'Profile updated successfully.', variant: 'success');
    }

    public function removePhoto(): void
    {
        $user = Auth::user();
        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
            $user->profile_photo_path = null;
            $user->save();
        }
    }
}; ?>

<div class="space-y-6">
    <flux:heading level="1" size="lg">Settings</flux:heading>

    <div class="space-y-8">
        <div class="border-b border-zinc-200 dark:border-zinc-700">
            <flux:navbar class="-mb-px">
                <flux:navbar.item :href="route('teams.account.profile', $team)" :accent="true" wire:navigate>
                    Profile
                </flux:navbar.item>
                <flux:navbar.item :href="route('teams.account.appearance', $team)" :accent="false" wire:navigate>
                    Appearance
                </flux:navbar.item>
                <flux:navbar.item :href="route('teams.account.devices', $team)" :accent="false" wire:navigate>
                    Devices
                </flux:navbar.item>
            </flux:navbar>
        </div>

        <div class="max-w-lg">
            <form wire:submit="updateProfileInformation" class="space-y-6">
                <flux:heading size="lg">Profile</flux:heading>
                <flux:text>Update your personal information.</flux:text>

                <div class="space-y-3">
                    <flux:heading>Profile photo</flux:heading>

                    <flux:file-upload wire:model="photo">
                        <div
                            class="relative flex size-20 cursor-pointer items-center justify-center rounded-full border border-zinc-200 bg-zinc-100 transition-colors hover:border-zinc-300 hover:bg-zinc-200 dark:border-white/10 dark:bg-white/10 dark:hover:border-white/10 hover:dark:bg-white/15 in-data-dragging:dark:bg-white/15"
                        >
                            @if ($photo)
                                <img src="{{ $photo?->temporaryUrl() }}" class="size-full rounded-full object-cover" />
                            @elseif (Auth::user()->profile_photo_path)
                                <img
                                    src="{{ Storage::disk('public')->url(Auth::user()->profile_photo_path) }}"
                                    class="size-full rounded-full object-cover"
                                />
                            @else
                                <flux:icon name="user" variant="solid" class="text-zinc-500 dark:text-zinc-400" />
                            @endif

                            <div class="absolute right-0 bottom-0 rounded-full bg-white dark:bg-zinc-800">
                                <flux:icon
                                    name="arrow-up-circle"
                                    variant="solid"
                                    class="text-zinc-500 dark:text-zinc-400"
                                />
                            </div>
                        </div>
                    </flux:file-upload>

                    @if (Auth::user()->profile_photo_path)
                        <div class="mt-3">
                            <flux:button wire:click="removePhoto" size="xs">Remove photo</flux:button>
                        </div>
                    @endif
                </div>

                <div class="space-y-6">
                    <flux:input wire:model="name" label="Name" type="text" required autofocus autocomplete="name" />

                    <flux:input wire:model="email" label="Email" type="email" required autocomplete="email" />
                </div>

                <flux:button type="submit" variant="primary" color="green">Save</flux:button>
            </form>
        </div>
    </div>
</div>
