{{-- Admin invitation creation interface --}}
<div class="space-y-6">
    {{-- Page header --}}
    <div>
        <flux:heading size="xl">Create Invitation</flux:heading>
        <flux:text class="mt-1">Invite someone to join the community directly.</flux:text>
    </div>

    @if ($showSuccess && $createdInvitation)
        {{-- Success state with invitation link --}}
        <flux:card class="space-y-4">
            <div class="flex items-start gap-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                    <flux:icon.check class="h-5 w-5 text-green-600 dark:text-green-400" />
                </div>
                <div>
                    <flux:heading size="lg">Invitation Created!</flux:heading>
                    <flux:text class="mt-1">
                        @if ($sendEmail)
                            An invitation has been sent to <strong>{{ $createdInvitation->email }}</strong>.
                        @else
                            Share the link below with <strong>{{ $createdInvitation->email }}</strong>.
                        @endif
                    </flux:text>
                </div>
            </div>

            {{-- Invitation link --}}
            <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:text size="sm" class="mb-2 font-medium">Invitation Link</flux:text>
                <div class="flex items-center gap-2" x-data="{ copied: false }">
                    <flux:input
                        type="text"
                        :value="$createdInvitation->getUrl()"
                        readonly
                        class="font-mono text-sm flex-1"
                    />
                    <flux:button
                        variant="primary"
                        size="sm"
                        x-on:click="
                            navigator.clipboard.writeText('{{ $createdInvitation->getUrl() }}');
                            copied = true;
                            setTimeout(() => copied = false, 2000);
                        "
                    >
                        <span x-show="!copied">Copy</span>
                        <span x-show="copied" x-cloak>Copied!</span>
                    </flux:button>
                </div>
                <flux:text size="sm" class="mt-2 text-zinc-500">
                    Expires: {{ $createdInvitation->expires_at->format('M j, Y \a\t g:i A') }}
                </flux:text>
            </div>

            {{-- Actions --}}
            <div class="flex gap-3">
                <flux:button wire:click="createAnother" variant="primary">
                    Create Another Invitation
                </flux:button>
                <flux:button href="{{ route('admin.invitations') }}" variant="ghost">
                    View All Invitations
                </flux:button>
            </div>
        </flux:card>
    @else
        {{-- Invitation form --}}
        <flux:card class="max-w-xl">
            <form wire:submit="create" class="space-y-4">
                {{-- Email field --}}
                <flux:input
                    wire:model="email"
                    type="email"
                    label="Email Address"
                    placeholder="person@example.com"
                    required
                />

                {{-- Name field (optional) --}}
                <flux:input
                    wire:model="name"
                    label="Name"
                    description="Optional - for personalized invitation email"
                    placeholder="John Doe"
                />

                {{-- Expiration field --}}
                <flux:select
                    wire:model="expiresInDays"
                    label="Expires In"
                >
                    <flux:select.option value="1">1 day</flux:select.option>
                    <flux:select.option value="3">3 days</flux:select.option>
                    <flux:select.option value="7">7 days</flux:select.option>
                    <flux:select.option value="14">14 days</flux:select.option>
                    <flux:select.option value="30">30 days</flux:select.option>
                </flux:select>

                {{-- Send email option --}}
                <flux:checkbox
                    wire:model="sendEmail"
                    label="Send invitation email"
                    description="Automatically send the invitation link to the email address"
                />

                {{-- Info callout --}}
                <flux:callout variant="info" icon="information-circle">
                    <flux:callout.text>
                        Users who register via invitation are automatically approved and can access the community immediately.
                    </flux:callout.text>
                </flux:callout>

                {{-- Submit button --}}
                <div class="flex gap-3">
                    <flux:button type="submit" variant="primary">
                        <span wire:loading.remove wire:target="create">Create Invitation</span>
                        <span wire:loading wire:target="create">Creating...</span>
                    </flux:button>
                    <flux:button href="{{ route('admin.invitations') }}" variant="ghost">
                        Cancel
                    </flux:button>
                </div>
            </form>
        </flux:card>
    @endif
</div>
