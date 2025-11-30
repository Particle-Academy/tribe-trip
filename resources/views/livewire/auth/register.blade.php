{{-- Registration form for new community members --}}
<div>
    <flux:card class="space-y-6">
        <div class="text-center">
            <flux:heading size="xl">Join the Community</flux:heading>
            <flux:text class="mt-2">Create your account to request access to shared resources.</flux:text>
        </div>

        <form wire:submit="register" class="space-y-4">
            {{-- Name field --}}
            <flux:input
                wire:model="form.name"
                label="Full Name"
                placeholder="Enter your full name"
                required
            />

            {{-- Email field --}}
            <flux:input
                wire:model="form.email"
                type="email"
                label="Email Address"
                placeholder="you@example.com"
                required
            />

            {{-- Phone field (optional) --}}
            <flux:input
                wire:model="form.phone"
                type="tel"
                label="Phone Number"
                description="Optional - for reservation reminders"
                placeholder="(555) 123-4567"
            />

            {{-- Password field --}}
            <flux:input
                wire:model="form.password"
                type="password"
                label="Password"
                placeholder="Create a secure password"
                viewable
                required
            />

            {{-- Confirm password field --}}
            <flux:input
                wire:model="form.password_confirmation"
                type="password"
                label="Confirm Password"
                placeholder="Confirm your password"
                viewable
                required
            />

            {{-- Info callout about approval process --}}
            <flux:callout variant="info" icon="information-circle">
                <flux:callout.heading>Approval Required</flux:callout.heading>
                <flux:callout.text>
                    After registration, an administrator will review your application.
                    You'll receive an email once your account is approved.
                </flux:callout.text>
            </flux:callout>

            {{-- Submit button --}}
            <flux:button type="submit" variant="primary" class="w-full">
                <span wire:loading.remove wire:target="register">Create Account</span>
                <span wire:loading wire:target="register">Creating Account...</span>
            </flux:button>
        </form>

        {{-- Login link --}}
        <div class="text-center">
            <flux:text size="sm">
                Already have an account?
                <flux:link href="{{ route('login') }}" class="font-medium">Sign in</flux:link>
            </flux:text>
        </div>
    </flux:card>
</div>

