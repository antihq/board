<?php

use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::auth'), Title('Register')] class extends Component {
    public string $name = '';

    public string $email = '';

    public bool $displayingRegisterForm = true;

    public function register(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
        ]);

        event(new Registered(($user = $this->createUser())));

        $user->sendOneTimePassword();

        $this->displayingRegisterForm = false;
    }

    protected function createUser(): User
    {
        return DB::transaction(function () {
            return tap(
                User::create([
                    'name' => $this->name,
                    'email' => $this->email,
                ]),
                function (User $user) {
                    $this->createTeam($user);
                },
            );
        });
    }

    protected function createTeam(User $user): void
    {
        $user->teams()->save(
            Team::forceCreate([
                'user_id' => $user->id,
                'name' => explode(' ', $user->name, 2)[0] . "'s Team",
                'personal' => true,
            ]),
        );
    }
}; ?>

<div class="isolate mx-auto flex min-h-dvh max-w-7xl items-center justify-center gap-12 max-lg:flex-col">
    <div>
        <h1
            class="text-5xl/[0.9] font-medium tracking-tight text-balance hyphens-auto text-zinc-950 max-lg:text-center sm:text-8xl/[0.8] md:text-9xl/[0.8]"
        >
            Kanban, decaffeinated.
        </h1>
    </div>

    <div class="w-full max-w-md">
        @if ($displayingRegisterForm)
            <div class="rounded-xl bg-white shadow-md ring-1 ring-black/5">
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

                        <flux:button variant="primary" color="green" type="submit" class="w-full text-base!">
                            Create account
                        </flux:button>

                        <flux:separator text="or" />

                        <flux:button href="/sign-in" class="w-full text-base!" wire:navigate>Sign in</flux:button>
                    </form>
                </div>
            </div>
        @else
            <livewire:one-time-password :email="$email" />
        @endif
    </div>
</div>
