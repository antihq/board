<div class="w-full max-w-md rounded-xl bg-white shadow-md ring-1 ring-black/5">
    <div class="p-7 sm:p-11">
        <form wire:submit="submitEmail" class="space-y-8">
            <div class="flex items-start">
                <a href="/" wire:navigate>
                    <img src="/logo@2x.png" alt="" class="h-9" />
                </a>
            </div>

            <div>
                <flux:heading level="1" class="text-base/6! font-medium">Welcome back!</flux:heading>
                <flux:text class="mt-1 text-sm/5">Sign in to your account to continue.</flux:text>
            </div>

            <flux:input
                wire:model="email"
                label="Email"
                type="email"
                required
                autofocus
            />

            <flux:button variant="primary" color="zinc" type="submit" class="w-full rounded-full! text-base!">
                Sign in
            </flux:button>
        </form>
    </div>
    <div class="m-1.5 rounded-lg bg-zinc-50 py-4 text-center text-sm/5 ring-1 ring-black/5">
        Don't have an account?
        <flux:link href="/register" :accent="false" wire:navigate>Sign up</flux:link>
    </div>
</div>
