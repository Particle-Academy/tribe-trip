{{-- Member reservations dashboard --}}
<div class="space-y-6">
    {{-- Page header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl" class="!text-[#3D4A36]">My Reservations</flux:heading>
            <flux:text class="mt-1 !text-[#5A6350]">Manage your resource bookings</flux:text>
        </div>
        <flux:button href="{{ route('member.resources') }}" variant="filled" icon="plus" class="!bg-[#4A5240] hover:!bg-[#3D4A36]">
            Book a Resource
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

    {{-- Filter tabs --}}
    <flux:tabs wire:model.live="filter">
        <flux:tab name="upcoming">
            Upcoming
            @if ($counts['upcoming'] > 0)
                <flux:badge size="sm" color="blue" class="ml-2">{{ $counts['upcoming'] }}</flux:badge>
            @endif
        </flux:tab>
        <flux:tab name="past">
            Past
            @if ($counts['past'] > 0)
                <flux:badge size="sm" color="zinc" class="ml-2">{{ $counts['past'] }}</flux:badge>
            @endif
        </flux:tab>
        <flux:tab name="cancelled">
            Cancelled
            @if ($counts['cancelled'] > 0)
                <flux:badge size="sm" color="zinc" class="ml-2">{{ $counts['cancelled'] }}</flux:badge>
            @endif
        </flux:tab>
    </flux:tabs>

    {{-- Reservations list --}}
    @if ($reservations->count() > 0)
        <div class="space-y-4">
            @foreach ($reservations as $reservation)
                <flux:card wire:key="reservation-{{ $reservation->id }}" class="flex flex-col sm:flex-row gap-4">
                    {{-- Resource image --}}
                    <div class="sm:w-40 flex-shrink-0">
                        <div class="aspect-video sm:aspect-square bg-[#E8E2D6] dark:bg-zinc-800 rounded-lg overflow-hidden">
                            @if ($reservation->resource->primaryImageUrl)
                                <img
                                    src="{{ $reservation->resource->primaryImageUrl }}"
                                    alt="{{ $reservation->resource->name }}"
                                    class="w-full h-full object-cover"
                                />
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <flux:icon name="{{ $reservation->resource->type->icon() }}" class="w-10 h-10 text-[#7A8B6E]" />
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Details --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2 mb-2">
                            <div>
                                <flux:heading size="lg" class="!text-[#3D4A36]">
                                    <a href="{{ route('member.resources.show', $reservation->resource) }}" class="hover:underline">
                                        {{ $reservation->resource->name }}
                                    </a>
                                </flux:heading>
                                <flux:badge size="sm" :color="$reservation->resource->type->color()">
                                    {{ $reservation->resource->type->label() }}
                                </flux:badge>
                            </div>
                            <flux:badge size="lg" :color="$reservation->status->color()">
                                {{ $reservation->status->label() }}
                            </flux:badge>
                        </div>

                        <div class="space-y-2 text-sm">
                            <div class="flex items-center gap-2 text-[#5A6350] dark:text-zinc-300">
                                <flux:icon name="calendar-days" class="w-4 h-4" />
                                <span>{{ $reservation->starts_at->format('l, F j, Y') }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-[#5A6350] dark:text-zinc-300">
                                <flux:icon name="clock" class="w-4 h-4" />
                                <span>{{ $reservation->starts_at->format('g:i A') }} - {{ $reservation->ends_at->format('g:i A') }}</span>
                                <span class="text-[#7A8B6E]">({{ $reservation->formatted_duration }})</span>
                            </div>
                            <div class="flex items-center gap-2 text-[#5A6350] dark:text-zinc-300">
                                <flux:icon name="banknotes" class="w-4 h-4" />
                                <span>{{ $reservation->resource->formatted_price }}</span>
                            </div>
                        </div>

                        @if ($reservation->notes)
                            <flux:text size="sm" class="!text-[#7A8B6E] mt-2 italic">
                                "{{ $reservation->notes }}"
                            </flux:text>
                        @endif

                        @if ($reservation->isCancelled() && $reservation->cancellation_reason)
                            <flux:callout variant="info" class="mt-3">
                                <flux:callout.text>
                                    <strong>Cancellation reason:</strong> {{ $reservation->cancellation_reason }}
                                </flux:callout.text>
                            </flux:callout>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="flex sm:flex-col gap-2 sm:w-32 flex-shrink-0">
                        @if ($reservation->isConfirmed() && !$reservation->usageLog && $reservation->starts_at->subMinutes(30)->isPast() && $reservation->ends_at->isFuture())
                            <flux:button href="{{ route('member.usage.checkout', $reservation) }}" variant="primary" size="sm" class="flex-1 sm:w-full">
                                Check Out
                            </flux:button>
                        @elseif ($reservation->isCheckedOut() && $reservation->usageLog?->isInProgress())
                            <flux:button href="{{ route('member.usage.checkin', $reservation->usageLog) }}" variant="primary" size="sm" class="flex-1 sm:w-full">
                                Check In
                            </flux:button>
                        @elseif ($reservation->canBeCancelled())
                            <flux:button wire:click="confirmCancel({{ $reservation->id }})" variant="danger" size="sm" class="flex-1 sm:w-full">
                                Cancel
                            </flux:button>
                        @endif

                        <flux:button href="{{ route('member.resources.show', $reservation->resource) }}" variant="ghost" size="sm" class="flex-1 sm:w-full">
                            View Resource
                        </flux:button>
                    </div>
                </flux:card>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $reservations->links() }}
        </div>
    @else
        <flux:card class="text-center py-12 !bg-white">
            <flux:icon name="calendar-days" class="w-12 h-12 text-[#7A8B6E] mx-auto mb-4" />
            <flux:heading size="lg" class="mb-2 !text-[#3D4A36]">
                @if ($filter === 'upcoming')
                    No upcoming reservations
                @elseif ($filter === 'past')
                    No past reservations
                @elseif ($filter === 'cancelled')
                    No cancelled reservations
                @else
                    No reservations found
                @endif
            </flux:heading>
            <flux:text class="!text-[#5A6350] mb-4">
                @if ($filter === 'upcoming')
                    Book a resource to see it here.
                @else
                    Your {{ $filter }} reservations will appear here.
                @endif
            </flux:text>
            @if ($filter === 'upcoming')
                <flux:button href="{{ route('member.resources') }}" variant="filled" icon="plus" class="!bg-[#4A5240] hover:!bg-[#3D4A36]">
                    Browse Resources
                </flux:button>
            @endif
        </flux:card>
    @endif

    {{-- Cancel confirmation modal --}}
    <flux:modal wire:model="showCancelModal">
        <div class="space-y-4">
            <flux:heading size="lg" class="!text-[#3D4A36]">Cancel Reservation</flux:heading>

            @if ($reservationToCancel)
                <flux:text class="!text-[#5A6350]">
                    Are you sure you want to cancel your reservation for
                    <strong class="text-[#3D4A36]">{{ $reservationToCancel->resource->name }}</strong> on
                    <strong class="text-[#3D4A36]">{{ $reservationToCancel->starts_at->format('F j, Y') }}</strong>?
                </flux:text>

                <flux:textarea
                    wire:model="cancellationReason"
                    label="Reason (optional)"
                    placeholder="Why are you cancelling this reservation?"
                    rows="3"
                />
            @endif

            <div class="flex justify-end gap-3 pt-4 border-t border-[#D4C9B8] dark:border-zinc-700">
                <flux:button wire:click="closeCancelModal" variant="ghost" class="!text-[#5A6350]">
                    Keep Reservation
                </flux:button>
                <flux:button wire:click="cancelReservation" variant="danger">
                    Cancel Reservation
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
