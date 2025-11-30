{{-- Admin member detail/edit view --}}
<div class="space-y-6">
    {{-- Page header with breadcrumb --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="{{ route('admin.members') }}">Members</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $user->name }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <flux:heading size="xl" class="mt-2">{{ $user->name }}</flux:heading>
        </div>

        <div class="flex gap-2">
            <flux:button href="{{ route('admin.members') }}" variant="ghost" icon="arrow-left">
                Back to Members
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

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Main info card --}}
        <div class="lg:col-span-2 space-y-6">
            <flux:card>
                <div class="flex items-center justify-between mb-4">
                    <flux:heading size="lg">Member Information</flux:heading>
                    @if (!$editing)
                        <flux:button wire:click="startEditing" variant="ghost" size="sm" icon="pencil">
                            Edit
                        </flux:button>
                    @endif
                </div>

                @if ($editing)
                    <form wire:submit="save" class="space-y-4">
                        <flux:input
                            wire:model="name"
                            label="Full Name"
                            required
                        />
                        <flux:input
                            wire:model="email"
                            type="email"
                            label="Email Address"
                            required
                        />
                        <flux:input
                            wire:model="phone"
                            label="Phone Number"
                        />

                        <div class="flex gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                            <flux:button type="submit" variant="primary">
                                Save Changes
                            </flux:button>
                            <flux:button wire:click="cancelEditing" variant="ghost">
                                Cancel
                            </flux:button>
                        </div>
                    </form>
                @else
                    <dl class="space-y-4">
                        <div class="flex items-center gap-4">
                            <flux:avatar size="lg" name="{{ $user->name }}" />
                            <div>
                                <dt class="text-sm text-zinc-500">Full Name</dt>
                                <dd class="font-medium">{{ $user->name }}</dd>
                            </div>
                        </div>

                        <div>
                            <dt class="text-sm text-zinc-500">Email Address</dt>
                            <dd>
                                <flux:link href="mailto:{{ $user->email }}">{{ $user->email }}</flux:link>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm text-zinc-500">Phone Number</dt>
                            <dd>{{ $user->phone ?: '—' }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm text-zinc-500">Member Since</dt>
                            <dd>{{ $user->created_at->format('F j, Y') }}</dd>
                        </div>
                    </dl>
                @endif
            </flux:card>

            {{-- Status actions card --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">Account Status</flux:heading>

                <div class="flex items-center gap-4 mb-6">
                    <flux:badge size="lg" :color="$user->status->color()">
                        {{ $user->status->label() }}
                    </flux:badge>

                    @if ($user->status_changed_at)
                        <flux:text size="sm" class="text-zinc-500">
                            since {{ $user->status_changed_at->format('M j, Y') }}
                        </flux:text>
                    @endif
                </div>

                @if ($user->status_reason)
                    <flux:callout variant="info" class="mb-6">
                        <flux:callout.text>
                            <strong>Reason:</strong> {{ $user->status_reason }}
                        </flux:callout.text>
                    </flux:callout>
                @endif

                @if ($user->id !== auth()->id())
                    <div class="flex flex-wrap gap-3">
                        @if ($user->isApproved())
                            <flux:button wire:click="openStatusModal('suspend')" variant="danger" icon="pause">
                                Suspend Account
                            </flux:button>
                        @elseif ($user->isSuspended())
                            <flux:button wire:click="openStatusModal('reactivate')" variant="primary" icon="play">
                                Reactivate Account
                            </flux:button>
                        @elseif ($user->isPending())
                            <flux:button wire:click="openStatusModal('approve')" variant="primary" icon="check">
                                Approve
                            </flux:button>
                            <flux:button wire:click="openStatusModal('reject')" variant="danger" icon="x-mark">
                                Reject
                            </flux:button>
                        @elseif ($user->isRejected())
                            <flux:button wire:click="openStatusModal('approve')" variant="primary" icon="check">
                                Approve Application
                            </flux:button>
                        @endif
                    </div>
                @else
                    <flux:callout variant="info" icon="information-circle">
                        <flux:callout.text>
                            You cannot modify your own account status.
                        </flux:callout.text>
                    </flux:callout>
                @endif
            </flux:card>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Role card --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">Role & Permissions</flux:heading>

                <div class="flex items-center gap-3 mb-4">
                    @if ($user->isAdmin())
                        <flux:badge size="lg" color="indigo">Admin</flux:badge>
                    @else
                        <flux:badge size="lg" color="zinc">Member</flux:badge>
                    @endif
                </div>

                <flux:text size="sm" class="text-zinc-500 mb-4">
                    @if ($user->isAdmin())
                        Admins can manage members, resources, reservations, and billing.
                    @else
                        Members can view resources, make reservations, and manage their own profile.
                    @endif
                </flux:text>

                @if ($user->id !== auth()->id())
                    @if ($user->isAdmin())
                        <flux:button wire:click="openRoleModal('demote')" variant="ghost" size="sm" class="w-full">
                            Demote to Member
                        </flux:button>
                    @else
                        <flux:button wire:click="openRoleModal('promote')" variant="ghost" size="sm" class="w-full">
                            Promote to Admin
                        </flux:button>
                    @endif
                @endif
            </flux:card>

            {{-- Quick stats card (placeholder for future usage data) --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">Activity Summary</flux:heading>

                <div class="space-y-3">
                    <div class="flex justify-between">
                        <flux:text size="sm" class="text-zinc-500">Total Reservations</flux:text>
                        <flux:text size="sm" class="font-medium">—</flux:text>
                    </div>
                    <div class="flex justify-between">
                        <flux:text size="sm" class="text-zinc-500">Last Login</flux:text>
                        <flux:text size="sm" class="font-medium">—</flux:text>
                    </div>
                    <div class="flex justify-between">
                        <flux:text size="sm" class="text-zinc-500">Outstanding Balance</flux:text>
                        <flux:text size="sm" class="font-medium">—</flux:text>
                    </div>
                </div>

                <flux:text size="xs" class="text-zinc-400 mt-4">
                    Detailed activity will be available after reservations are made.
                </flux:text>
            </flux:card>
        </div>
    </div>

    {{-- Status change modal --}}
    <flux:modal wire:model="showStatusModal">
        <div class="space-y-4">
            <flux:heading size="lg">
                @php
                    $actionTitle = match($statusAction) {
                        'suspend' => 'Suspend Member',
                        'reactivate' => 'Reactivate Member',
                        'approve' => 'Approve Member',
                        'reject' => 'Reject Member',
                        default => 'Change Status',
                    };
                @endphp
                {{ $actionTitle }}
            </flux:heading>

            <flux:text>
                @php
                    $actionMessage = match($statusAction) {
                        'suspend' => "Are you sure you want to suspend {$user->name}'s account? They will not be able to access the application.",
                        'reactivate' => "Are you sure you want to reactivate {$user->name}'s account? They will regain full access.",
                        'approve' => "Are you sure you want to approve {$user->name}'s registration? They will gain access to the application.",
                        'reject' => "Are you sure you want to reject {$user->name}'s registration?",
                        default => 'Are you sure?',
                    };
                @endphp
                {{ $actionMessage }}
            </flux:text>

            <flux:textarea
                wire:model="statusReason"
                label="Reason (optional)"
                placeholder="Enter a reason for this action..."
                rows="3"
            />

            <div class="flex justify-end gap-3 pt-4">
                <flux:button wire:click="closeStatusModal" variant="ghost">
                    Cancel
                </flux:button>
                <flux:button
                    wire:click="confirmStatusChange"
                    :variant="in_array($statusAction, ['suspend', 'reject']) ? 'danger' : 'primary'"
                >
                    Confirm {{ ucfirst($statusAction) }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Role change modal --}}
    <flux:modal wire:model="showRoleModal">
        <div class="space-y-4">
            <flux:heading size="lg">
                {{ $roleAction === 'promote' ? 'Promote to Admin' : 'Demote to Member' }}
            </flux:heading>

            <flux:text>
                @if ($roleAction === 'promote')
                    Are you sure you want to promote <strong>{{ $user->name }}</strong> to Admin?
                    They will gain full administrative access to manage members, resources, and billing.
                @else
                    Are you sure you want to demote <strong>{{ $user->name }}</strong> to Member?
                    They will lose administrative access.
                @endif
            </flux:text>

            <div class="flex justify-end gap-3 pt-4">
                <flux:button wire:click="closeRoleModal" variant="ghost">
                    Cancel
                </flux:button>
                <flux:button wire:click="confirmRoleChange" variant="primary">
                    Confirm
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
