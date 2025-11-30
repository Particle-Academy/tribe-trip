{{-- Admin invitation management list --}}
<div class="space-y-6">
    {{-- Page header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Invitations</flux:heading>
            <flux:text class="mt-1">Manage community member invitations.</flux:text>
        </div>

        <flux:button href="{{ route('admin.invitations.create') }}" variant="primary" icon="plus">
            Create Invitation
        </flux:button>
    </div>

    {{-- Flash messages --}}
    @if (session('success'))
        <flux:callout variant="success" icon="check-circle" dismissible>
            {{ session('success') }}
        </flux:callout>
    @endif

    @if (session('error'))
        <flux:callout variant="danger" icon="exclamation-circle" dismissible>
            {{ session('error') }}
        </flux:callout>
    @endif

    {{-- Filters --}}
    <div class="flex flex-col gap-4 sm:flex-row">
        <div class="w-full sm:w-64">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search by email or name..."
                icon="magnifying-glass"
                clearable
            />
        </div>

        <flux:select wire:model.live="status" class="w-full sm:w-48">
            <flux:select.option value="">All Statuses</flux:select.option>
            <flux:select.option value="pending">Pending ({{ $statusCounts['pending'] }})</flux:select.option>
            <flux:select.option value="accepted">Accepted ({{ $statusCounts['accepted'] }})</flux:select.option>
            <flux:select.option value="revoked">Revoked ({{ $statusCounts['revoked'] }})</flux:select.option>
            <flux:select.option value="expired">Expired ({{ $statusCounts['expired'] }})</flux:select.option>
        </flux:select>
    </div>

    {{-- Invitations table --}}
    @if ($invitations->isEmpty())
        <flux:card class="text-center py-12">
            <flux:icon.envelope class="mx-auto h-12 w-12 text-zinc-400" />
            <flux:heading size="lg" class="mt-4">No invitations found</flux:heading>
            <flux:text class="mt-2">
                @if ($search || $status)
                    Try adjusting your filters.
                @else
                    Create your first invitation to get started.
                @endif
            </flux:text>
            @if (!$search && !$status)
                <flux:button href="{{ route('admin.invitations.create') }}" variant="primary" class="mt-4">
                    Create Invitation
                </flux:button>
            @endif
        </flux:card>
    @else
        <flux:card class="p-0 overflow-hidden">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Invitee</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Invited By</flux:table.column>
                    <flux:table.column>Sent</flux:table.column>
                    <flux:table.column class="text-right">Actions</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($invitations as $invitation)
                        <flux:table.row wire:key="invitation-{{ $invitation->id }}">
                            <flux:table.cell>
                                <div>
                                    <div class="font-medium">{{ $invitation->email }}</div>
                                    @if ($invitation->name)
                                        <div class="text-sm text-zinc-500">{{ $invitation->name }}</div>
                                    @endif
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                @php
                                    $isExpiredPending = $invitation->isPending() && $invitation->expires_at->isPast();
                                    $displayStatus = $isExpiredPending ? 'expired' : $invitation->status->value;
                                    $color = match($displayStatus) {
                                        'pending' => 'amber',
                                        'accepted' => 'green',
                                        'revoked' => 'red',
                                        'expired' => 'zinc',
                                        default => 'zinc',
                                    };
                                @endphp
                                <flux:badge size="sm" :color="$color">
                                    {{ ucfirst($displayStatus) }}
                                </flux:badge>
                                @if ($invitation->isPending() && !$isExpiredPending)
                                    <div class="text-xs text-zinc-500 mt-1">
                                        Expires {{ $invitation->expires_at->diffForHumans() }}
                                    </div>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="flex items-center gap-2">
                                    <flux:avatar size="xs" name="{{ $invitation->invitedBy->name }}" />
                                    <span class="text-sm">{{ $invitation->invitedBy->name }}</span>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:text size="sm">
                                    {{ $invitation->created_at->format('M j, Y') }}
                                </flux:text>
                            </flux:table.cell>

                            <flux:table.cell class="text-right">
                                @if ($invitation->isUsable())
                                    <div class="flex items-center justify-end gap-2">
                                        <flux:button
                                            wire:click="resend({{ $invitation->id }})"
                                            wire:loading.attr="disabled"
                                            variant="ghost"
                                            size="sm"
                                        >
                                            Resend
                                        </flux:button>
                                        <flux:button
                                            wire:click="revoke({{ $invitation->id }})"
                                            wire:confirm="Are you sure you want to revoke this invitation?"
                                            variant="danger"
                                            size="sm"
                                        >
                                            Revoke
                                        </flux:button>
                                    </div>
                                @elseif ($invitation->isAccepted())
                                    <flux:text size="sm" class="text-zinc-500">
                                        Registered {{ $invitation->accepted_at->diffForHumans() }}
                                    </flux:text>
                                @else
                                    <flux:text size="sm" class="text-zinc-400">—</flux:text>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $invitations->links() }}
        </div>
    @endif
</div>
