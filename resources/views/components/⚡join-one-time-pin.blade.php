<?php

use App\Models\Team;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use Spatie\OneTimePasswords\Models\Concerns\HasOneTimePasswords;
use Spatie\OneTimePasswords\Rules\OneTimePasswordRule;

new class extends Component
{
    public ?string $email = null;

    public string $oneTimePassword = '';

    public string $redirectTo = '/';

    public Team $team;

    public string $invitationCode;

    public bool $displayingOtpForm = true;

    public function mount(string $email, Team $team, string $invitationCode): void
    {
        $this->email = $email;
        $this->team = $team;
        $this->invitationCode = $invitationCode;

        $this->redirectTo = route('teams.join', ['team' => $team, 'invitation_code' => $invitationCode]);
    }

    public function submitOneTimePassword()
    {
        $user = $this->findUser();

        $this->validate([
            'oneTimePassword' => ['required', new OneTimePasswordRule($user)],
        ]);

        $this->authenticate($user);

        $updated = $this->team
            ->where('id', $this->team->id)
            ->where('invitation_code_uses_count', '<', $this->team->invitation_code_max_uses)
            ->increment('invitation_code_uses_count');

        if (! $updated) {
            abort(403, 'Invitation link has reached its maximum number of uses');
        }

        Auth::user()
            ->joinedTeams()
            ->attach($this->team->id, ['role' => 'member']);

        return $this->redirect(route('teams.show', ['team' => $this->team]), navigate: true);
    }

    public function authenticate(Authenticatable $user): void
    {
        Auth::login($user, remember: true);
    }

    protected function rateLimitHit(): bool
    {
        $rateLimitKey = "one-time-password-component-send-code.{$this->email}";

        if (RateLimiter::tooManyAttempts($rateLimitKey, 10)) {
            return true;
        }

        RateLimiter::hit($rateLimitKey, 60);

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

<div class="space-y-8">
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

        <flux:button variant="primary" color="green" type="submit" class="w-full text-base!">
            Verify and join
        </flux:button>
    </form>
</div>
