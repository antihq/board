<?php

use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::simple'), Title('Sign up')] class extends Component
{
    public string $name = '';

    public string $email = '';

    public bool $displayingRegisterForm = true;

    public function register(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
        ]);

        event(new Registered($user = $this->createUser()));

        $user->sendOneTimePassword();

        $this->displayingRegisterForm = false;
    }

    protected function createUser(): User
    {
        return DB::transaction(function () {
            return tap(User::create([
                'name' => $this->name,
                'email' => $this->email,
            ]), function (User $user) {
                $this->createTeam($user);
            });
        });
    }

    protected function createTeam(User $user): void
    {
        $user->teams()->save(Team::forceCreate([
            'user_id' => $user->id,
            'name' => explode(' ', $user->name, 2)[0]."'s Team",
            'personal' => true,
        ]));
    }
}; ?>

<div class="isolate flex min-h-dvh items-center justify-center">
    @if ($displayingRegisterForm)
        <div class="w-full max-w-md rounded-xl bg-white shadow-md ring-1 ring-black/5">
            <div class="p-7 sm:p-11">
                <form wire:submit="register" class="space-y-8">
                    <div class="flex items-start">
                        <a href="/" wire:navigate>
                            <img src="/logo@2x.png" alt="" class="h-9" />
                        </a>
                    </div>

                    <div>
                        <flux:heading level="1" class="text-base/6! font-medium">Create an account</flux:heading>
                        <flux:text class="mt-1 text-sm/5">
                            Enter your details to create your account and verify your email
                        </flux:text>
                    </div>

                    <flux:input
                        wire:model="name"
                        label="Name"
                        type="text"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="Full name"
                    />

                    <flux:input
                        wire:model="email"
                        label="Email address"
                        type="email"
                        required
                        autocomplete="email"
                        placeholder="email@example.com"
                    />

                    <flux:button variant="primary" color="zinc" type="submit" class="w-full rounded-full!">
                        Create account
                    </flux:button>
                </form>
            </div>
            <div class="m-1.5 rounded-lg bg-zinc-50 py-4 text-center text-sm/5 ring-1 ring-black/5">
                Already have an account?
                <flux:link href="/login" :accent="false" wire:navigate>Log in</flux:link>
            </div>
        </div>
    @else
        <livewire:one-time-password :email="$email" />
    @endif
</div>
