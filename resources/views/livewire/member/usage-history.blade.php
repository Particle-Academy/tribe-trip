{{-- Member usage history dashboard --}}
<div class="space-y-6">
    {{-- Page header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl" class="!text-[#3D4A36]">Usage History</flux:heading>
            <flux:text class="mt-1 !text-[#5A6350]">View your complete resource usage history</flux:text>
        </div>
    </div>

    {{-- Summary stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <flux:card class="!p-4 !bg-white">
            <flux:text size="sm" class="!text-[#7A8B6E]">Total Uses</flux:text>
            <flux:heading size="lg" class="!text-[#3D4A36]">{{ $stats['total_uses'] }}</flux:heading>
        </flux:card>
        <flux:card class="!p-4 !bg-white">
            <flux:text size="sm" class="!text-[#7A8B6E]">Total Spent</flux:text>
            <flux:heading size="lg" class="!text-[#3D4A36]">${{ number_format($stats['total_cost'], 2) }}</flux:heading>
        </flux:card>
        <flux:card class="!p-4 !bg-white">
            <flux:text size="sm" class="!text-[#7A8B6E]">Total Hours</flux:text>
            <flux:heading size="lg" class="!text-[#3D4A36]">{{ number_format($stats['total_hours'], 1) }}</flux:heading>
        </flux:card>
        <flux:card class="!p-4 !bg-white">
            <flux:text size="sm" class="!text-[#7A8B6E]">Total Distance</flux:text>
            <flux:heading size="lg" class="!text-[#3D4A36]">{{ number_format($stats['total_distance'], 1) }}</flux:heading>
        </flux:card>
    </div>

    {{-- Filters --}}
    <flux:card class="!bg-white">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
            <div class="flex-1">
                <flux:select wire:model.live="resource" class="w-full sm:w-48">
                    <option value="">All Resources</option>
                    @foreach ($resources as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </flux:select>
            </div>
            <div>
                <flux:select wire:model.live="status" class="w-full sm:w-40">
                    <option value="">All Statuses</option>
                    @foreach ($statuses as $s)
                        <option value="{{ $s->value }}">{{ $s->label() }}</option>
                    @endforeach
                </flux:select>
            </div>
        </div>
    </flux:card>

    {{-- Usage logs list --}}
    @if ($logs->count() > 0)
        <div class="space-y-4">
            @foreach ($logs as $log)
                <flux:card wire:key="log-{{ $log->id }}" class="!bg-white">
                    <div class="flex flex-col sm:flex-row gap-4">
                        {{-- Resource image --}}
                        <div class="sm:w-24 flex-shrink-0">
                            <div class="aspect-square bg-[#E8E2D6] dark:bg-zinc-800 rounded-lg overflow-hidden">
                                @if ($log->resource->primaryImageUrl)
                                    <img
                                        src="{{ $log->resource->primaryImageUrl }}"
                                        alt="{{ $log->resource->name }}"
                                        class="w-full h-full object-cover"
                                    />
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <flux:icon name="{{ $log->resource->type->icon() }}" class="w-8 h-8 text-[#7A8B6E]" />
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Usage details --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2 mb-2">
                                <div>
                                    <flux:heading size="lg" class="!text-[#3D4A36]">
                                        {{ $log->resource->name }}
                                    </flux:heading>
                                    <flux:text size="sm" class="!text-[#7A8B6E]">
                                        {{ $log->checked_out_at->format('l, F j, Y') }}
                                    </flux:text>
                                </div>
                                <flux:badge size="lg" :color="$log->status->color()">
                                    {{ $log->status->label() }}
                                </flux:badge>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-3">
                                <div>
                                    <flux:text size="sm" class="!text-[#7A8B6E]">Check Out</flux:text>
                                    <flux:text class="font-medium !text-[#3D4A36]">{{ $log->checked_out_at->format('g:i A') }}</flux:text>
                                </div>
                                <div>
                                    <flux:text size="sm" class="!text-[#7A8B6E]">Check In</flux:text>
                                    <flux:text class="font-medium !text-[#3D4A36]">
                                        {{ $log->checked_in_at?->format('g:i A') ?? '—' }}
                                    </flux:text>
                                </div>
                                <div>
                                    <flux:text size="sm" class="!text-[#7A8B6E]">Duration</flux:text>
                                    <flux:text class="font-medium !text-[#3D4A36]">
                                        {{ $log->formatted_duration ?? '—' }}
                                    </flux:text>
                                </div>
                                <div>
                                    <flux:text size="sm" class="!text-[#7A8B6E]">Cost</flux:text>
                                    <flux:text class="font-bold !text-[#3D4A36]">
                                        {{ $log->formatted_cost ?? '—' }}
                                    </flux:text>
                                </div>
                            </div>

                            @if ($log->formatted_distance)
                                <div class="mt-2">
                                    <flux:text size="sm" class="!text-[#7A8B6E]">
                                        Distance: <span class="text-[#3D4A36] font-medium">{{ $log->formatted_distance }}</span>
                                    </flux:text>
                                </div>
                            @endif

                            {{-- Invoice status --}}
                            @if ($log->isInvoiced())
                                <div class="mt-2">
                                    <flux:badge size="sm" color="green">
                                        <flux:icon name="check" class="w-3 h-3 mr-1" />
                                        Invoiced
                                    </flux:badge>
                                </div>
                            @endif
                        </div>

                        {{-- Actions --}}
                        <div class="flex sm:flex-col gap-2 sm:w-28 flex-shrink-0">
                            <flux:button wire:click="viewDetails({{ $log->id }})" variant="filled" size="sm" class="flex-1 sm:w-full !bg-[#4A5240] hover:!bg-[#3D4A36]">
                                Details
                            </flux:button>
                        </div>
                    </div>
                </flux:card>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $logs->links() }}
        </div>
    @else
        <flux:card class="text-center py-12 !bg-white">
            <flux:icon name="clipboard-document-list" class="w-12 h-12 text-[#7A8B6E] mx-auto mb-4" />
            <flux:heading size="lg" class="mb-2 !text-[#3D4A36]">No usage history yet</flux:heading>
            <flux:text class="!text-[#5A6350] mb-4">
                @if ($resource || $status)
                    No usage logs match your filters.
                @else
                    Your usage history will appear here after you check out resources.
                @endif
            </flux:text>
            @if (!$resource && !$status)
                <flux:button href="{{ route('member.resources') }}" variant="filled" icon="magnifying-glass" class="!bg-[#4A5240] hover:!bg-[#3D4A36]">
                    Browse Resources
                </flux:button>
            @endif
        </flux:card>
    @endif

    {{-- Usage detail modal --}}
    <flux:modal wire:model="showDetailModal" class="max-w-lg">
        @if ($selectedLog)
            <div class="space-y-6">
                {{-- Header --}}
                <div class="flex justify-between items-start">
                    <div>
                        <flux:heading size="xl" class="!text-[#3D4A36]">{{ $selectedLog->resource->name }}</flux:heading>
                        <flux:text class="!text-[#7A8B6E]">{{ $selectedLog->checked_out_at->format('l, F j, Y') }}</flux:text>
                    </div>
                    <flux:badge size="lg" :color="$selectedLog->status->color()">
                        {{ $selectedLog->status->label() }}
                    </flux:badge>
                </div>

                {{-- Check out/in details --}}
                <div class="grid grid-cols-2 gap-4 p-4 bg-[#F2EDE4] rounded-lg">
                    <div>
                        <flux:text size="sm" class="!text-[#7A8B6E]">Check Out</flux:text>
                        <flux:text class="font-medium !text-[#3D4A36]">{{ $selectedLog->checked_out_at->format('g:i A') }}</flux:text>
                        @if ($selectedLog->start_reading !== null)
                            <flux:text size="sm" class="!text-[#7A8B6E]">Reading: {{ number_format($selectedLog->start_reading, 1) }}</flux:text>
                        @endif
                    </div>
                    <div>
                        <flux:text size="sm" class="!text-[#7A8B6E]">Check In</flux:text>
                        <flux:text class="font-medium !text-[#3D4A36]">{{ $selectedLog->checked_in_at?->format('g:i A') ?? '—' }}</flux:text>
                        @if ($selectedLog->end_reading !== null)
                            <flux:text size="sm" class="!text-[#7A8B6E]">Reading: {{ number_format($selectedLog->end_reading, 1) }}</flux:text>
                        @endif
                    </div>
                </div>

                {{-- Photos --}}
                @if ($selectedLog->start_photo_url || $selectedLog->end_photo_url)
                    <div class="grid grid-cols-2 gap-4">
                        @if ($selectedLog->start_photo_url)
                            <div>
                                <flux:text size="sm" class="!text-[#7A8B6E] mb-2">Start Photo</flux:text>
                                <img src="{{ $selectedLog->start_photo_url }}" alt="Start reading" class="rounded-lg w-full" />
                            </div>
                        @endif
                        @if ($selectedLog->end_photo_url)
                            <div>
                                <flux:text size="sm" class="!text-[#7A8B6E] mb-2">End Photo</flux:text>
                                <img src="{{ $selectedLog->end_photo_url }}" alt="End reading" class="rounded-lg w-full" />
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Usage summary --}}
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-4 bg-[#E8E2D6] rounded-lg text-center">
                        <flux:text size="sm" class="!text-[#7A8B6E]">Duration</flux:text>
                        <flux:heading size="lg" class="!text-[#3D4A36]">{{ $selectedLog->formatted_duration ?? '—' }}</flux:heading>
                    </div>
                    <div class="p-4 bg-[#E8E2D6] rounded-lg text-center">
                        <flux:text size="sm" class="!text-[#7A8B6E]">Cost</flux:text>
                        <flux:heading size="lg" class="!text-[#3D4A36]">{{ $selectedLog->formatted_cost ?? '—' }}</flux:heading>
                    </div>
                </div>

                @if ($selectedLog->formatted_distance)
                    <div class="p-4 bg-[#E8E2D6] rounded-lg text-center">
                        <flux:text size="sm" class="!text-[#7A8B6E]">Distance Traveled</flux:text>
                        <flux:heading size="lg" class="!text-[#3D4A36]">{{ $selectedLog->formatted_distance }}</flux:heading>
                    </div>
                @endif

                {{-- Notes --}}
                @if ($selectedLog->start_notes || $selectedLog->end_notes)
                    <div class="space-y-2">
                        @if ($selectedLog->start_notes)
                            <div>
                                <flux:text size="sm" class="!text-[#7A8B6E]">Check-out notes:</flux:text>
                                <flux:text class="!text-[#5A6350] italic">"{{ $selectedLog->start_notes }}"</flux:text>
                            </div>
                        @endif
                        @if ($selectedLog->end_notes)
                            <div>
                                <flux:text size="sm" class="!text-[#7A8B6E]">Check-in notes:</flux:text>
                                <flux:text class="!text-[#5A6350] italic">"{{ $selectedLog->end_notes }}"</flux:text>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Actions --}}
                <div class="flex justify-end pt-4 border-t border-[#D4C9B8] dark:border-zinc-700">
                    <flux:button wire:click="closeDetailModal" variant="filled" class="!bg-[#4A5240] hover:!bg-[#3D4A36]">
                        Close
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
