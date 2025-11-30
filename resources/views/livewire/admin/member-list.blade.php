{{-- Admin member management list --}}
<div class="space-y-6">
    {{-- Page header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Members</flux:heading>
            <flux:text class="mt-1">Manage community members and their access.</flux:text>
        </div>

        <div class="flex gap-2">
            <flux:button href="{{ route('admin.invitations.create') }}" variant="primary" icon="user-plus">
                Invite Member
            </flux:button>
        </div>
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

    {{-- Stats cards --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <flux:card class="text-center">
            <flux:text size="sm" class="text-zinc-500">Total Members</flux:text>
            <flux:heading size="2xl">{{ $counts['total'] }}</flux:heading>
        </flux:card>
        <flux:card class="text-center">
            <flux:text size="sm" class="text-green-600 dark:text-green-400">Active</flux:text>
            <flux:heading size="2xl">{{ $counts['approved'] }}</flux:heading>
        </flux:card>
        <flux:card class="text-center">
            <flux:text size="sm" class="text-amber-600 dark:text-amber-400">Pending</flux:text>
            <flux:heading size="2xl">{{ $counts['pending'] }}</flux:heading>
        </flux:card>
        <flux:card class="text-center">
            <flux:text size="sm" class="text-zinc-500">Suspended</flux:text>
            <flux:heading size="2xl">{{ $counts['suspended'] }}</flux:heading>
        </flux:card>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col gap-4 sm:flex-row">
        <div class="w-full sm:w-64">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search members..."
                icon="magnifying-glass"
                clearable
            />
        </div>

        <flux:select wire:model.live="status" class="w-full sm:w-48">
            <flux:select.option value="">All Statuses</flux:select.option>
            <flux:select.option value="approved">Active ({{ $counts['approved'] }})</flux:select.option>
            <flux:select.option value="pending">Pending ({{ $counts['pending'] }})</flux:select.option>
            <flux:select.option value="suspended">Suspended ({{ $counts['suspended'] }})</flux:select.option>
            <flux:select.option value="rejected">Rejected ({{ $counts['rejected'] }})</flux:select.option>
        </flux:select>

        <flux:select wire:model.live="role" class="w-full sm:w-40">
            <flux:select.option value="">All Roles</flux:select.option>
            <flux:select.option value="admin">Admins ({{ $counts['admins'] }})</flux:select.option>
            <flux:select.option value="member">Members ({{ $counts['members'] }})</flux:select.option>
        </flux:select>
    </div>

    {{-- Members table --}}
    @if ($members->isEmpty())
        <flux:card class="text-center py-12">
            <flux:icon.users class="mx-auto h-12 w-12 text-zinc-400" />
            <flux:heading size="lg" class="mt-4">No members found</flux:heading>
            <flux:text class="mt-2">
                @if ($search || $status || $role)
                    Try adjusting your filters.
                @else
                    Invite your first member to get started.
                @endif
            </flux:text>
        </flux:card>
    @else
        <flux:card class="p-0 overflow-hidden">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Member</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Role</flux:table.column>
                    <flux:table.column>Joined</flux:table.column>
                    <flux:table.column class="text-right">Actions</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($members as $member)
                        <flux:table.row wire:key="member-{{ $member->id }}">
                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    <flux:avatar size="sm" name="{{ $member->name }}" />
                                    <div>
                                        <div class="font-medium">{{ $member->name }}</div>
                                        <div class="text-sm text-zinc-500">{{ $member->email }}</div>
                                    </div>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:badge size="sm" :color="$member->status->color()">
                                    {{ $member->status->label() }}
                                </flux:badge>
                            </flux:table.cell>

                            <flux:table.cell>
                                @if ($member->isAdmin())
                                    <flux:badge size="sm" color="indigo">Admin</flux:badge>
                                @else
                                    <flux:text size="sm">Member</flux:text>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:text size="sm">
                                    {{ $member->created_at->format('M j, Y') }}
                                </flux:text>
                            </flux:table.cell>

                            <flux:table.cell class="text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <flux:button
                                        href="{{ route('admin.members.show', $member) }}"
                                        variant="ghost"
                                        size="sm"
                                    >
                                        View
                                    </flux:button>

                                    @if ($member->id !== auth()->id())
                                        @if ($member->isApproved() && !$member->isAdmin())
                                            <flux:button
                                                wire:click="suspend({{ $member->id }})"
                                                wire:confirm="Are you sure you want to suspend {{ $member->name }}?"
                                                variant="danger"
                                                size="sm"
                                            >
                                                Suspend
                                            </flux:button>
                                        @elseif ($member->isSuspended())
                                            <flux:button
                                                wire:click="reactivate({{ $member->id }})"
                                                variant="primary"
                                                size="sm"
                                            >
                                                Reactivate
                                            </flux:button>
                                        @endif
                                    @else
                                        <flux:text size="sm" class="text-zinc-400">You</flux:text>
                                    @endif
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $members->links() }}
        </div>
    @endif
</div>
