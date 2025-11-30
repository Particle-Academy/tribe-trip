{{-- Member resource detail with availability calendar and booking --}}
<div class="space-y-6">
    {{-- Page header with breadcrumb --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="{{ route('member.resources') }}">Resources</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $resource->name }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <flux:heading size="xl" class="mt-2 !text-[#3D4A36]">{{ $resource->name }}</flux:heading>
        </div>
        <flux:button href="{{ route('member.resources') }}" variant="ghost" icon="arrow-left" class="!text-[#4A5240]">
            Back to Resources
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

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Main content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Resource images --}}
            @if ($resource->images->count() > 0)
                <flux:card class="!p-0 overflow-hidden">
                    <div class="aspect-video relative">
                        <img
                            src="{{ $resource->primaryImageUrl }}"
                            alt="{{ $resource->name }}"
                            class="w-full h-full object-cover"
                        />
                    </div>
                    @if ($resource->images->count() > 1)
                        <div class="p-4 flex gap-2 overflow-x-auto">
                            @foreach ($resource->images as $image)
                                <img
                                    src="{{ $image->url }}"
                                    alt="{{ $resource->name }}"
                                    class="w-20 h-20 rounded-lg object-cover cursor-pointer ring-2 {{ $image->is_primary ? 'ring-[#4A5240]' : 'ring-transparent hover:ring-[#D4C9B8]' }}"
                                />
                            @endforeach
                        </div>
                    @endif
                </flux:card>
            @endif

            {{-- Description --}}
            @if ($resource->description)
                <flux:card class="!bg-white">
                    <flux:heading size="lg" class="mb-3 !text-[#3D4A36]">About this Resource</flux:heading>
                    <flux:text class="!text-[#5A6350] dark:text-zinc-300 whitespace-pre-line">
                        {{ $resource->description }}
                    </flux:text>
                </flux:card>
            @endif

            {{-- Availability calendar --}}
            <flux:card class="!bg-white">
                <div class="flex items-center justify-between mb-4">
                    <flux:heading size="lg" class="!text-[#3D4A36]">Availability</flux:heading>
                    <div class="flex items-center gap-2">
                        <flux:button wire:click="previousMonth" variant="ghost" size="sm" icon="chevron-left" class="!text-[#4A5240]" />
                        <flux:text class="font-medium min-w-32 text-center !text-[#3D4A36]">{{ $calendarMonth }} {{ $calendarYear }}</flux:text>
                        <flux:button wire:click="nextMonth" variant="ghost" size="sm" icon="chevron-right" class="!text-[#4A5240]" />
                    </div>
                </div>

                {{-- Calendar grid --}}
                <div class="overflow-hidden rounded-lg border border-[#D4C9B8] dark:border-zinc-700">
                    {{-- Day headers --}}
                    <div class="grid grid-cols-7 bg-[#E8E2D6] dark:bg-zinc-800 border-b border-[#D4C9B8] dark:border-zinc-700">
                        @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
                            <div class="py-2 text-center text-xs font-medium text-[#5A6350] uppercase">{{ $day }}</div>
                        @endforeach
                    </div>

                    {{-- Calendar weeks --}}
                    @foreach ($calendarWeeks as $week)
                        <div class="grid grid-cols-7 divide-x divide-[#D4C9B8] dark:divide-zinc-700 border-b border-[#D4C9B8] dark:border-zinc-700 last:border-b-0">
                            @foreach ($week as $day)
                                <button
                                    type="button"
                                    wire:click="selectDate('{{ $day['date'] }}')"
                                    @class([
                                        'relative p-2 min-h-16 text-left transition-colors',
                                        'bg-white dark:bg-zinc-900' => $day['isCurrentMonth'] && !$day['isPast'],
                                        'bg-[#F2EDE4] dark:bg-zinc-800/50' => !$day['isCurrentMonth'] || $day['isPast'],
                                        'hover:bg-[#E8E2D6] dark:hover:bg-[#4A5240]/20' => !$day['isPast'],
                                        'cursor-not-allowed opacity-50' => $day['isPast'],
                                        'ring-2 ring-inset ring-[#4A5240]' => $selectedDate === $day['date'],
                                    ])
                                    @if ($day['isPast']) disabled @endif
                                >
                                    <span @class([
                                        'inline-flex items-center justify-center w-6 h-6 rounded-full text-sm',
                                        'bg-[#4A5240] text-white' => $day['isToday'],
                                        'font-medium text-[#3D4A36]' => $day['isCurrentMonth'] && !$day['isToday'],
                                        'text-[#7A8B6E]' => !$day['isCurrentMonth'],
                                    ])>
                                        {{ $day['day'] }}
                                    </span>

                                    {{-- Multi-day reservation visual indicator bar --}}
                                    @if ($day['hasMultiDay'] && !$day['isPast'])
                                        <div @class([
                                            'absolute bottom-4 h-1.5 bg-[#8B5A3C]/60 dark:bg-[#C9B896]/60',
                                            'left-0 right-1/2 rounded-l-full' => $day['isMultiDayStart'],
                                            'left-1/2 right-0 rounded-r-full' => $day['isMultiDayEnd'],
                                            'left-0 right-0' => $day['isMultiDayMiddle'],
                                            'left-0 right-0 rounded-full' => !$day['isMultiDayStart'] && !$day['isMultiDayMiddle'] && !$day['isMultiDayEnd'],
                                        ])></div>
                                    @endif

                                    @if ($day['hasReservations'] && !$day['isPast'])
                                        <div class="absolute bottom-1 right-1">
                                            <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-medium bg-[#8B5A3C]/10 text-[#8B5A3C] dark:bg-[#8B5A3C]/30 dark:text-[#C9B896] rounded-full">
                                                {{ $day['reservationCount'] }}
                                            </span>
                                        </div>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @endforeach
                </div>

                {{-- Legend --}}
                <div class="flex flex-wrap gap-4 mt-4 text-sm text-[#5A6350]">
                    <div class="flex items-center gap-2">
                        <span class="w-4 h-4 bg-[#4A5240] rounded-full"></span>
                        <span>Today</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-4 h-4 bg-[#8B5A3C]/10 dark:bg-[#8B5A3C]/30 rounded-full"></span>
                        <span>Has bookings</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-8 h-1.5 bg-[#8B5A3C]/60 dark:bg-[#C9B896]/60 rounded-full"></span>
                        <span>Multi-day</span>
                    </div>
                </div>
            </flux:card>

            {{-- Selected day reservations --}}
            @if ($selectedDate)
                <flux:card class="!bg-white">
                    <div class="flex items-center justify-between mb-4">
                        <flux:heading size="lg" class="!text-[#3D4A36]">
                            {{ \Carbon\Carbon::parse($selectedDate)->format('l, F j, Y') }}
                        </flux:heading>
                        @if (!\Carbon\Carbon::parse($selectedDate)->isPast())
                            <flux:button wire:click="openBookingModal('{{ $selectedDate }}')" variant="filled" size="sm" icon="plus" class="!bg-[#4A5240] hover:!bg-[#3D4A36]">
                                Book This Day
                            </flux:button>
                        @endif
                    </div>

                    @if ($this->selectedDateReservations->count() > 0)
                        <div class="space-y-3">
                            @foreach ($this->selectedDateReservations as $reservation)
                                <div class="flex items-center gap-4 p-3 bg-[#E8E2D6] dark:bg-zinc-800 rounded-lg">
                                    <div class="flex-1">
                                        <flux:text class="font-medium !text-[#3D4A36]">
                                            {{ $reservation->starts_at->format('g:i A') }} - {{ $reservation->ends_at->format('g:i A') }}
                                        </flux:text>
                                        <flux:text size="sm" class="!text-[#7A8B6E]">
                                            {{ $reservation->formatted_duration }}
                                        </flux:text>
                                    </div>
                                    <flux:badge size="sm" :color="$reservation->status->color()">
                                        {{ $reservation->status->label() }}
                                    </flux:badge>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <flux:text class="!text-[#5A6350] text-center py-4">
                            No reservations for this day. This resource is available!
                        </flux:text>
                    @endif
                </flux:card>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Pricing card --}}
            <flux:card class="!bg-white">
                <flux:heading size="lg" class="mb-4 !text-[#3D4A36]">Pricing</flux:heading>

                <div class="text-center mb-4">
                    <flux:text class="text-3xl font-bold !text-[#4A5240] dark:!text-[#7A8B6E]">
                        {{ $resource->formatted_price }}
                    </flux:text>
                </div>

                <flux:separator class="my-4" />

                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <flux:text class="!text-[#7A8B6E]">Type</flux:text>
                        <flux:badge size="sm" :color="$resource->type->color()">{{ $resource->type->label() }}</flux:badge>
                    </div>

                    @if ($resource->requires_approval)
                        <div class="flex justify-between">
                            <flux:text class="!text-[#7A8B6E]">Approval</flux:text>
                            <flux:text class="font-medium !text-[#8B5A3C]">Required</flux:text>
                        </div>
                    @else
                        <div class="flex justify-between">
                            <flux:text class="!text-[#7A8B6E]">Approval</flux:text>
                            <flux:text class="font-medium !text-[#4A5240]">Instant</flux:text>
                        </div>
                    @endif

                    {{-- Display booking duration limit info --}}
                    @if ($resource->maxBookingDays() === 0)
                        <div class="flex justify-between">
                            <flux:text class="!text-[#7A8B6E]">Duration</flux:text>
                            <flux:text class="font-medium !text-[#3D4A36]">Single day only</flux:text>
                        </div>
                    @elseif ($resource->maxBookingDays())
                        <div class="flex justify-between">
                            <flux:text class="!text-[#7A8B6E]">Max Duration</flux:text>
                            <flux:text class="font-medium !text-[#3D4A36]">{{ $resource->maxBookingDays() }} days</flux:text>
                        </div>
                    @endif

                    @if ($resource->advance_booking_days)
                        <div class="flex justify-between">
                            <flux:text class="!text-[#7A8B6E]">Book Ahead</flux:text>
                            <flux:text class="font-medium !text-[#3D4A36]">Up to {{ $resource->advance_booking_days }} days</flux:text>
                        </div>
                    @endif
                </div>

                <flux:separator class="my-4" />

                <flux:button wire:click="openBookingModal" variant="filled" class="w-full !bg-[#4A5240] hover:!bg-[#3D4A36]" icon="calendar-days">
                    Book Now
                </flux:button>
            </flux:card>

            {{-- Quick actions --}}
            <flux:card class="!bg-white">
                <flux:heading size="lg" class="mb-4 !text-[#3D4A36]">Quick Links</flux:heading>

                <div class="space-y-2">
                    <flux:button href="{{ route('member.reservations') }}" variant="ghost" class="w-full justify-start !text-[#4A5240]" icon="clock">
                        My Reservations
                    </flux:button>
                    <flux:button href="{{ route('member.resources') }}" variant="ghost" class="w-full justify-start !text-[#4A5240]" icon="squares-2x2">
                        Browse All Resources
                    </flux:button>
                </div>
            </flux:card>
        </div>
    </div>

    {{-- Booking modal --}}
    <flux:modal wire:model="showBookingModal">
        <div class="space-y-4">
            <flux:heading size="lg" class="!text-[#3D4A36]">Book {{ $resource->name }}</flux:heading>

            <form wire:submit="submitReservation" class="space-y-4">
                {{-- Date selection: multi-day vs single-day based on resource settings --}}
                @if ($resource->allowsMultiDayBooking())
                    {{-- Multi-day booking: show start and end dates --}}
                    <div class="grid grid-cols-2 gap-4">
                        <flux:input
                            wire:model="bookingDate"
                            type="date"
                            label="Start Date"
                            :min="now()->format('Y-m-d')"
                            required
                        />
                        <flux:input
                            wire:model="endDate"
                            type="date"
                            label="End Date"
                            :min="now()->format('Y-m-d')"
                            required
                        />
                    </div>
                    @if ($resource->maxBookingDays())
                        <flux:text size="sm" class="!text-[#7A8B6E]">
                            Maximum {{ $resource->maxBookingDays() }} days per booking
                        </flux:text>
                    @endif
                @else
                    {{-- Single-day booking only --}}
                    <flux:input
                        wire:model="bookingDate"
                        type="date"
                        label="Date"
                        :min="now()->format('Y-m-d')"
                        required
                    />
                    <flux:text size="sm" class="!text-[#7A8B6E]">
                        This resource is for single-day bookings only
                    </flux:text>
                @endif

                <div class="grid grid-cols-2 gap-4">
                    <flux:input
                        wire:model="startTime"
                        type="time"
                        label="Start Time"
                        required
                    />
                    <flux:input
                        wire:model="endTime"
                        type="time"
                        label="End Time"
                        required
                    />
                </div>

                <flux:textarea
                    wire:model="notes"
                    label="Notes (optional)"
                    placeholder="Any special requirements or notes..."
                    rows="3"
                />

                {{-- Price estimate --}}
                <flux:callout variant="info" icon="banknotes">
                    <flux:callout.heading>Estimated Cost</flux:callout.heading>
                    <flux:callout.text>
                        {{ $resource->formatted_price }}
                        @if ($resource->requires_approval)
                            <br><span class="text-[#8B5A3C]">This booking requires admin approval.</span>
                        @endif
                    </flux:callout.text>
                </flux:callout>

                <div class="flex justify-end gap-3 pt-4 border-t border-[#D4C9B8] dark:border-zinc-700">
                    <flux:button wire:click="closeBookingModal" variant="ghost" type="button" class="!text-[#5A6350]">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="filled" class="!bg-[#4A5240] hover:!bg-[#3D4A36]">
                        {{ $resource->requires_approval ? 'Request Booking' : 'Confirm Booking' }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
