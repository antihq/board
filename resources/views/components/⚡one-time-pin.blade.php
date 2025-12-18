<?php

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use Spatie\OneTimePasswords\Models\Concerns\HasOneTimePasswords;
use Spatie\OneTimePasswords\Rules\OneTimePasswordRule;

new class extends Component {
    public ?string $email = null;

    public string $oneTimePassword = '';

    public bool $isFixedEmail = false;

    public string $redirectTo = '/';

    public bool $displayingEmailForm = true;

    public function mount(?string $redirectTo = null, ?string $email = ''): void
    {
        $this->email = $email;

        if ($this->email) {
            $this->isFixedEmail = true;
            $this->displayingEmailForm = false;
        }

        $this->redirectTo = $redirectTo ?? config('one-time-passwords.redirect_successful_authentication_to');
    }

    public function submitEmail(): void
    {
        $this->validate([
            'email' => 'required|email',
        ]);

        $user = $this->findUser();

        if (! $user) {
            $this->addError('email', 'We could not find a user with that email address.');

            return;
        }

        $this->sendCode();

        $this->displayingEmailForm = false;
    }

    public function resendCode(): void
    {
        $this->sendCode();
    }

    public function displayEmailForm(): void
    {
        $this->email = null;

        $this->displayingEmailForm = true;
    }

    public function submitOneTimePassword()
    {
        $user = $this->findUser();

        $this->validate([
            'oneTimePassword' => ['required', new OneTimePasswordRule($user)],
        ]);

        $this->authenticate($user);

        return $this->redirect($this->redirectTo);
    }

    public function authenticate(Authenticatable $user): void
    {
        Auth::login($user, remember: true);
    }

    public function showViewName(): string
    {
        return $this->displayingEmailForm ? 'email-form' : 'one-time-password-form';
    }

    protected function sendCode(): void
    {
        $user = $this->findUser();

        if ($this->rateLimitHit()) {
            return;
        }

        $this->displayingEmailForm = false;

        $user->sendOneTimePassword();
    }

    protected function rateLimitHit(): bool
    {
        $rateLimitKey = "one-time-password-component-send-code.{$this->email}";

        if (RateLimiter::tooManyAttempts($rateLimitKey, 10)) {
            return true;
        }

        RateLimiter::hit($rateLimitKey, 60); // 60 seconds decay time

        return false;
    }

    /**
     * @return HasOneTimePasswords&Model&Authenticatable
     */
    protected function findUser(): ?Authenticatable
    {
        $authenticatableModel = config('auth.providers.users.model');

        return $authenticatableModel::firstWhere('email', $this->email);
    }
};
?>

<div>
    @if ($this->displayingEmailForm)
        <form wire:submit="submitEmail" class="space-y-8">
            <flux:input wire:model="email" label="Email" type="email" required autofocus />

            <flux:button variant="primary" color="green" type="submit" class="w-full text-base!">Sign in</flux:button>
        </form>
    @else
        <div x-data="{ resendText: 'Resend code', isResending: false }" class="space-y-8">
            <flux:callout icon="inbox-arrow-down">
                <flux:callout.heading>Check your email</flux:callout.heading>
                <flux:callout.text>Then enter the verification code from the email.</flux:callout.text>
            </flux:callout>

            <form wire:submit="submitOneTimePassword" class="space-y-8">
                <div class="text-center">
                    <flux:otp
                        wire:model="oneTimePassword"
                        :length="config('one-time-passwords.password_length')"
                        label="Verification code"
                        submit="auto"
                        class="mx-auto"
                    />
                </div>

                <div class="space-y-2">
                    <flux:button variant="primary" color="green" type="submit" class="w-full text-base!">
                        Verify
                    </flux:button>
                    <flux:button
                        @click="
                            if (!isResending) {
                                isResending = true;
                                resendText = 'Code sent';
                                $wire.resendCode();
                                setTimeout(() => {
                                    resendText = 'Resend code';
                                    isResending = false;
                                }, 2000);
                            }
                        "
                        variant="subtle"
                        class="w-full"
                        x-text="resendText"
                    />
                </div>
            </form>
        </div>
    @endif
</div>
