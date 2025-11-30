{{-- Registration form for invited users --}}
<div>
    @if (!$isValid)
        {{-- Invalid invitation state --}}
        <flux:card class="space-y-6 text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                <flux:icon.exclamation-triangle class="h-8 w-8 text-red-600 dark:text-red-400" />
            </div>

            <div>
                <flux:heading size="xl">Invalid Invitation</flux:heading>
                <flux:text class="mt-2">{{ $invalidReason }}</flux:text>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
                <flux:button href="{{ route('register') }}" variant="primary">
                    Register Without Invitation
                </flux:button>
                <flux:button href="{{ route('login') }}" variant="ghost">
                    Sign In
                </flux:button>
            </div>
        </flux:card>
    @else
        {{-- Valid invitation - show registration form --}}
        <flux:card class="space-y-6">
            <div class="text-center">
                <flux:heading size="xl">Welcome to TribeTrip!</flux:heading>
                <flux:text class="mt-2">
                    You've been invited by <strong>{{ $invitation->invitedBy->name }}</strong> to join the community.
                </flux:text>
            </div>

            {{-- Show invited email (locked) --}}
            <flux:callout variant="info" icon="envelope">
                <flux:callout.text>
                    You're registering with: <strong>{{ $invitation->email }}</strong>
                </flux:callout.text>
            </flux:callout>

            <form wire:submit="register" class="space-y-4">
                {{-- Name field --}}
                <flux:input
                    wire:model="name"
                    label="Full Name"
                    placeholder="Enter your full name"
                    required
                />

                {{-- Phone field (optional) --}}
                <flux:input
                    wire:model="phone"
                    type="tel"
                    label="Phone Number"
                    description="Optional - for reservation reminders"
                    placeholder="(555) 123-4567"
                />

                {{-- Password field --}}
                <flux:input
                    wire:model="password"
                    type="password"
                    label="Password"
                    placeholder="Create a secure password"
                    viewable
                    required
                />

                {{-- Confirm password field --}}
                <flux:input
                    wire:model="password_confirmation"
                    type="password"
                    label="Confirm Password"
                    placeholder="Confirm your password"
                    viewable
                    required
                />

                {{-- Success callout --}}
                <flux:callout variant="success" icon="check-circle">
                    <flux:callout.text>
                        As an invited member, you'll have immediate access to the community after completing registration.
                    </flux:callout.text>
                </flux:callout>

                {{-- Submit button --}}
                <flux:button type="submit" variant="primary" class="w-full">
                    <span wire:loading.remove wire:target="register">Complete Registration</span>
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
    @endif
</div>
