{{-- Admin usage log list view --}}
<div class="space-y-6">
    {{-- Page header --}}
    <div>
        <flux:heading size="xl">Usage Logs</flux:heading>
        <flux:text class="mt-1 text-zinc-500">View and verify resource usage records</flux:text>
    </div>

    {{-- Flash messages --}}
    @if (session('success'))
        <flux:callout variant="success" icon="check-circle" dismissible>
            {{ session('success') }}
        </flux:callout>
    @endif

    {{-- Stats cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
        <flux:card class="!p-4">
            <flux:text size="sm" class="text-zinc-500">Total</flux:text>
            <flux:heading size="lg">{{ $counts['total'] }}</flux:heading>
        </flux:card>
        <flux:card class="!p-4">
            <flux:text size="sm" class="text-zinc-500">In Progress</flux:text>
            <flux:heading size="lg" class="text-blue-600">{{ $counts['in_progress'] }}</flux:heading>
        </flux:card>
        <flux:card class="!p-4">
            <flux:text size="sm" class="text-zinc-500">Pending Review</flux:text>
            <flux:heading size="lg" class="text-amber-600">{{ $counts['pending'] }}</flux:heading>
        </flux:card>
        <flux:card class="!p-4">
            <flux:text size="sm" class="text-zinc-500">Verified</flux:text>
            <flux:heading size="lg" class="text-green-600">{{ $counts['verified'] }}</flux:heading>
        </flux:card>
        <flux:card class="!p-4">
            <flux:text size="sm" class="text-zinc-500">Disputed</flux:text>
            <flux:heading size="lg" class="text-red-600">{{ $counts['disputed'] }}</flux:heading>
        </flux:card>
    </div>

    {{-- Filters --}}
    <flux:card>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
            <div class="flex-1">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    icon="magnifying-glass"
                    placeholder="Search by member name or email..."
                />
            </div>
            <div class="flex gap-4">
                <flux:select wire:model.live="status" class="w-40">
                    <option value="">All Statuses</option>
                    @foreach ($statuses as $s)
                        <option value="{{ $s->value }}">{{ $s->label() }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="resource" class="w-48">
                    <option value="">All Resources</option>
                    @foreach ($resources as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </flux:select>
            </div>
        </div>
    </flux:card>

    {{-- Usage logs table --}}
    @if ($logs->count() > 0)
        <flux:card class="!p-0 overflow-hidden">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Member</flux:table.column>
                    <flux:table.column>Resource</flux:table.column>
                    <flux:table.column>Check Out</flux:table.column>
                    <flux:table.column>Check In</flux:table.column>
                    <flux:table.column>Usage</flux:table.column>
                    <flux:table.column>Cost</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Actions</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($logs as $log)
                        <flux:table.row wire:key="log-{{ $log->id }}">
                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    <flux:avatar size="sm" name="{{ $log->user->name }}" />
                                    <div>
                                        <flux:text class="font-medium">{{ $log->user->name }}</flux:text>
                                        <flux:text size="sm" class="text-zinc-500">{{ $log->user->email }}</flux:text>
                                    </div>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:text class="font-medium">{{ $log->resource->name }}</flux:text>
                                <flux:badge size="sm" :color="$log->resource->type->color()">
                                    {{ $log->resource->type->label() }}
                                </flux:badge>
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="space-y-1">
                                    <flux:text size="sm">{{ $log->checked_out_at->format('M j, g:i A') }}</flux:text>
                                    @if ($log->start_reading !== null)
                                        <flux:text size="sm" class="text-zinc-500">
                                            Reading: {{ number_format($log->start_reading, 1) }}
                                        </flux:text>
                                    @endif
                                    @if ($log->start_photo_path)
                                        <flux:button wire:click="viewPhoto({{ $log->id }}, 'start')" variant="ghost" size="sm">
                                            <flux:icon name="photo" class="w-4 h-4" />
                                        </flux:button>
                                    @endif
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                @if ($log->checked_in_at)
                                    <div class="space-y-1">
                                        <flux:text size="sm">{{ $log->checked_in_at->format('M j, g:i A') }}</flux:text>
                                        @if ($log->end_reading !== null)
                                            <flux:text size="sm" class="text-zinc-500">
                                                Reading: {{ number_format($log->end_reading, 1) }}
                                            </flux:text>
                                        @endif
                                        @if ($log->end_photo_path)
                                            <flux:button wire:click="viewPhoto({{ $log->id }}, 'end')" variant="ghost" size="sm">
                                                <flux:icon name="photo" class="w-4 h-4" />
                                            </flux:button>
                                        @endif
                                    </div>
                                @else
                                    <flux:text size="sm" class="text-zinc-400">In progress...</flux:text>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="space-y-1">
                                    @if ($log->formatted_duration)
                                        <flux:text size="sm">{{ $log->formatted_duration }}</flux:text>
                                    @endif
                                    @if ($log->formatted_distance)
                                        <flux:text size="sm" class="text-zinc-500">{{ $log->formatted_distance }}</flux:text>
                                    @endif
                                    @if (!$log->formatted_duration && !$log->formatted_distance)
                                        <flux:text size="sm" class="text-zinc-400">—</flux:text>
                                    @endif
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:text class="font-medium">
                                    {{ $log->formatted_cost ?? '—' }}
                                </flux:text>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:badge size="sm" :color="$log->status->color()">
                                    {{ $log->status->label() }}
                                </flux:badge>
                            </flux:table.cell>

                            <flux:table.cell>
                                @if ($log->isCompleted() || $log->isDisputed())
                                    <flux:button wire:click="openVerifyModal({{ $log->id }})" variant="ghost" size="sm" icon="shield-check">
                                        Review
                                    </flux:button>
                                @elseif ($log->isVerified())
                                    <flux:text size="sm" class="text-green-600">
                                        <flux:icon name="check-circle" class="w-4 h-4 inline" /> Verified
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
        <div class="mt-6">
            {{ $logs->links() }}
        </div>
    @else
        <flux:card class="text-center py-12">
            <flux:icon name="clipboard-document-list" class="w-12 h-12 text-zinc-400 mx-auto mb-4" />
            <flux:heading size="lg" class="mb-2">No usage logs found</flux:heading>
            <flux:text class="text-zinc-500">
                @if ($search || $status || $resource)
                    Try adjusting your search or filter criteria.
                @else
                    Usage logs will appear here when members check out resources.
                @endif
            </flux:text>
        </flux:card>
    @endif

    {{-- Photo viewer modal --}}
    <flux:modal wire:model="showPhotoModal">
        <div class="space-y-4">
            <flux:heading size="lg">{{ $photoType }} Photo</flux:heading>

            @if ($photoUrl)
                <img src="{{ $photoUrl }}" alt="{{ $photoType }}" class="w-full rounded-lg" />
            @else
                <flux:text class="text-zinc-500">No photo available.</flux:text>
            @endif

            <div class="flex justify-end">
                <flux:button wire:click="closePhotoModal" variant="ghost">
                    Close
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Verification modal --}}
    <flux:modal wire:model="showVerifyModal">
        <div class="space-y-4">
            <flux:heading size="lg">Review Usage Log</flux:heading>

            @if ($logToVerify)
                <div class="space-y-4">
                    {{-- Summary --}}
                    <div class="grid grid-cols-2 gap-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4">
                        <div>
                            <flux:text size="sm" class="text-zinc-500">Member</flux:text>
                            <flux:text class="font-medium">{{ $logToVerify->user->name }}</flux:text>
                        </div>
                        <div>
                            <flux:text size="sm" class="text-zinc-500">Resource</flux:text>
                            <flux:text class="font-medium">{{ $logToVerify->resource->name }}</flux:text>
                        </div>
                        <div>
                            <flux:text size="sm" class="text-zinc-500">Duration</flux:text>
                            <flux:text class="font-medium">{{ $logToVerify->formatted_duration ?? '—' }}</flux:text>
                        </div>
                        <div>
                            <flux:text size="sm" class="text-zinc-500">Distance</flux:text>
                            <flux:text class="font-medium">{{ $logToVerify->formatted_distance ?? '—' }}</flux:text>
                        </div>
                        <div>
                            <flux:text size="sm" class="text-zinc-500">Start Reading</flux:text>
                            <flux:text class="font-medium">{{ $logToVerify->start_reading !== null ? number_format($logToVerify->start_reading, 1) : '—' }}</flux:text>
                        </div>
                        <div>
                            <flux:text size="sm" class="text-zinc-500">End Reading</flux:text>
                            <flux:text class="font-medium">{{ $logToVerify->end_reading !== null ? number_format($logToVerify->end_reading, 1) : '—' }}</flux:text>
                        </div>
                    </div>

                    {{-- Photos --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <flux:text size="sm" class="text-zinc-500 mb-2">Start Photo</flux:text>
                            @if ($logToVerify->start_photo_url)
                                <img src="{{ $logToVerify->start_photo_url }}" alt="Start" class="rounded-lg w-full" />
                            @else
                                <div class="bg-zinc-100 dark:bg-zinc-800 rounded-lg h-32 flex items-center justify-center">
                                    <flux:text size="sm" class="text-zinc-400">No photo</flux:text>
                                </div>
                            @endif
                        </div>
                        <div>
                            <flux:text size="sm" class="text-zinc-500 mb-2">End Photo</flux:text>
                            @if ($logToVerify->end_photo_url)
                                <img src="{{ $logToVerify->end_photo_url }}" alt="End" class="rounded-lg w-full" />
                            @else
                                <div class="bg-zinc-100 dark:bg-zinc-800 rounded-lg h-32 flex items-center justify-center">
                                    <flux:text size="sm" class="text-zinc-400">No photo</flux:text>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Calculated cost --}}
                    <flux:callout variant="info" icon="banknotes">
                        <flux:callout.heading>Calculated Cost</flux:callout.heading>
                        <flux:callout.text>{{ $logToVerify->formatted_cost ?? 'Unable to calculate' }}</flux:callout.text>
                    </flux:callout>

                    {{-- Admin notes --}}
                    <flux:textarea
                        wire:model="adminNotes"
                        label="Admin Notes"
                        placeholder="Add any notes about this usage log..."
                        rows="3"
                    />
                </div>

                <div class="flex justify-between gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:button wire:click="dispute" variant="danger">
                        Mark Disputed
                    </flux:button>
                    <div class="flex gap-3">
                        <flux:button wire:click="closeVerifyModal" variant="ghost">
                            Cancel
                        </flux:button>
                        <flux:button wire:click="verify" variant="primary">
                            Verify Usage
                        </flux:button>
                    </div>
                </div>
            @endif
        </div>
    </flux:modal>
</div>
