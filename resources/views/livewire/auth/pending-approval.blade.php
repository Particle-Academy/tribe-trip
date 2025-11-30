{{-- Pending approval status page --}}
<div>
    <flux:card class="space-y-6 text-center">
        {{-- Success icon --}}
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30">
            <flux:icon.clock class="h-8 w-8 text-amber-600 dark:text-amber-400" />
        </div>

        <div>
            <flux:heading size="xl">Registration Received</flux:heading>
            <flux:text class="mt-2">
                Thank you for registering! Your application is now pending review.
            </flux:text>
        </div>

        {{-- Info callout --}}
        <flux:callout variant="info" icon="information-circle" class="text-left">
            <flux:callout.heading>What happens next?</flux:callout.heading>
            <flux:callout.text>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    <li>An administrator will review your application</li>
                    <li>You'll receive an email notification with the decision</li>
                    <li>Once approved, you can log in and access community resources</li>
                </ul>
            </flux:callout.text>
        </flux:callout>

        {{-- Actions --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
            <flux:button href="{{ route('login') }}" variant="primary">
                Back to Login
            </flux:button>
            <flux:button href="/" variant="ghost">
                Return Home
            </flux:button>
        </div>
    </flux:card>
</div>

