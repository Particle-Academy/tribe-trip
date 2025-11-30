{{-- Admin approval queue for pending registrations --}}
<div class="space-y-6">
    {{-- Page header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Approval Queue</flux:heading>
            <flux:text class="mt-1">Review and approve pending member registrations.</flux:text>
        </div>

        {{-- Search input --}}
        <div class="w-full sm:w-64">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search by name or email..."
                icon="magnifying-glass"
                clearable
            />
        </div>
    </div>

    {{-- Flash messages --}}
    @if (session('success'))
        <flux:callout variant="success" icon="check-circle" dismissible>
            {{ session('success') }}
        </flux:callout>
    @endif

    {{-- Pending users table --}}
    @if ($pendingUsers->isEmpty())
        <flux:card class="text-center py-12">
            <flux:icon.check-circle class="mx-auto h-12 w-12 text-green-500" />
            <flux:heading size="lg" class="mt-4">All caught up!</flux:heading>
            <flux:text class="mt-2">No pending registrations to review.</flux:text>
        </flux:card>
    @else
        <flux:card class="p-0 overflow-hidden">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Applicant</flux:table.column>
                    <flux:table.column>Contact</flux:table.column>
                    <flux:table.column>Registered</flux:table.column>
                    <flux:table.column class="text-right">Actions</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($pendingUsers as $user)
                        <flux:table.row wire:key="user-{{ $user->id }}">
                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    <flux:avatar size="sm" name="{{ $user->name }}" />
                                    <div>
                                        <div class="font-medium">{{ $user->name }}</div>
                                        <flux:badge size="sm" color="amber">Pending</flux:badge>
                                    </div>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="text-sm">
                                    <div>{{ $user->email }}</div>
                                    @if ($user->phone)
                                        <div class="text-zinc-500">{{ $user->phone }}</div>
                                    @endif
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:text size="sm">
                                    {{ $user->created_at->format('M j, Y') }}
                                    <span class="text-zinc-500">{{ $user->created_at->format('g:i A') }}</span>
                                </flux:text>
                            </flux:table.cell>

                            <flux:table.cell class="text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <flux:button
                                        wire:click="approve({{ $user->id }})"
                                        wire:loading.attr="disabled"
                                        variant="primary"
                                        size="sm"
                                    >
                                        <span wire:loading.remove wire:target="approve({{ $user->id }})">Approve</span>
                                        <span wire:loading wire:target="approve({{ $user->id }})">...</span>
                                    </flux:button>

                                    <flux:button
                                        wire:click="openRejectModal({{ $user->id }})"
                                        variant="danger"
                                        size="sm"
                                    >
                                        Reject
                                    </flux:button>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $pendingUsers->links() }}
        </div>
    @endif

    {{-- Rejection modal --}}
    <flux:modal wire:model="showRejectModal" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">Reject Registration</flux:heading>

            <flux:text>
                Are you sure you want to reject this registration? This action will prevent the user from accessing the community.
            </flux:text>

            <flux:textarea
                wire:model="rejectionReason"
                label="Reason (optional)"
                placeholder="Provide a reason for rejection..."
                rows="3"
            />

            <div class="flex justify-end gap-3">
                <flux:button wire:click="closeRejectModal" variant="ghost">
                    Cancel
                </flux:button>
                <flux:button wire:click="reject" variant="danger">
                    Reject Registration
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
