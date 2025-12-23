<?php

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
                <flux:navbar.item :href="route('settings.profile')" :accent="true" wire:navigate>
                    Profile
                </flux:navbar.item>
                <flux:navbar.item :href="route('settings.appearance')" :accent="false" wire:navigate>
                    Appearance
                </flux:navbar.item>
                <flux:navbar.item :href="route('settings.devices')" :accent="false" wire:navigate>
                    Devices
                </flux:navbar.item>
            </flux:navbar>
        </div>

        <div class="max-w-lg">
            <form wire:submit="updateProfileInformation" class="space-y-6">
                <flux:heading size="lg">Profile</flux:heading>
                <flux:text>Update your personal information.</flux:text>

                <flux:file-upload wire:model="photo" label="Profile photo">
                    <flux:file-upload.dropzone
                        heading="Drop file here or click to browse"
                        text="JPG, PNG, GIF up to 10MB"
                    />
                </flux:file-upload>

                @if (Auth::user()->profile_photo_path)
                    <div class="flex flex-col gap-2">
                        <flux:file-item
                            heading="Current profile photo"
                            :image="Storage::disk('public')->url(Auth::user()->profile_photo_path)"
                        >
                            <x-slot name="actions">
                                <flux:file-item.remove wire:click="removePhoto" aria-label="Remove profile photo" />
                            </x-slot>
                        </flux:file-item>
                    </div>
                @endif

                <div class="space-y-6">
                    <flux:input wire:model="name" label="Name" type="text" required autofocus autocomplete="name" />

                    <flux:input wire:model="email" label="Email" type="email" required autocomplete="email" />
                </div>

                <flux:button type="submit" variant="primary" color="green">Save</flux:button>
            </form>
        </div>
    </div>
</div>
