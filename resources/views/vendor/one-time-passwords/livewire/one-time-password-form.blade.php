<div x-data="{ resendText: 'Resend code', isResending: false }" class="w-full max-w-md rounded-xl bg-white shadow-md ring-1 ring-black/5">
    <div class="p-7 sm:p-11">
        <form wire:submit="submitOneTimePassword" class="space-y-8">
            <div class="flex items-start">
                <a href="/" wire:navigate>
                    <img src="/logo@2x.png" alt="" class="h-9" />
                </a>
            </div>

            <div>
                <flux:heading level="1" class="text-base/6! font-medium">Check your email</flux:heading>
                <flux:text class="mt-1 text-sm/5">Then enter the verification code from the email.</flux:text>
            </div>

            <div class="text-center">
                <flux:otp
                    wire:model="oneTimePassword"
                    :length="config('one-time-passwords.password_length')"
                    label="Verification code"
                    submit="auto"
                    class="mx-auto"
                />
            </div>

            <flux:button variant="primary" color="zinc" type="submit" class="w-full rounded-full! text-base!">
                Verify
            </flux:button>
        </form>
    </div>
    <div class="m-1.5 rounded-lg bg-zinc-50 text-center text-sm/5 ring-1 ring-black/5">
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
            variant="ghost"
            class="w-full"
            x-text="resendText"></flux:button>
    </div>
</div>
