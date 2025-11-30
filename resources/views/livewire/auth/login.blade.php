{{-- Login form for community members --}}
<div>
    <flux:card class="space-y-6">
        <div class="text-center">
            <flux:heading size="xl">Welcome Back</flux:heading>
            <flux:text class="mt-2">Sign in to access your community resources.</flux:text>
        </div>

        <form wire:submit="login" class="space-y-4">
            {{-- Email field --}}
            <flux:input
                wire:model="email"
                type="email"
                label="Email Address"
                placeholder="you@example.com"
                required
                autofocus
            />

            {{-- Password field --}}
            <flux:input
                wire:model="password"
                type="password"
                label="Password"
                placeholder="Enter your password"
                viewable
                required
            />

            {{-- Remember me checkbox --}}
            <flux:checkbox wire:model="remember" label="Remember me" />

            {{-- Submit button --}}
            <flux:button type="submit" variant="primary" class="w-full">
                <span wire:loading.remove wire:target="login">Sign In</span>
                <span wire:loading wire:target="login">Signing In...</span>
            </flux:button>
        </form>

        {{-- Register link --}}
        <div class="text-center">
            <flux:text size="sm">
                Don't have an account?
                <flux:link href="{{ route('register') }}" class="font-medium">Register</flux:link>
            </flux:text>
        </div>
    </flux:card>
</div>

