<?php

namespace App\Livewire\Member;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\User;
use App\Notifications\NewReservationAlert;
use App\Notifications\ReservationConfirmed;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Member resource detail component.
 *
 * Shows resource info, availability calendar, and booking form.
 */
#[Layout('components.layouts.app')]
class ResourceDetail extends Component
{
    #[Locked]
    public Resource $resource;

    // Calendar state
    public string $selectedDate = '';

    public array $calendarWeeks = [];

    public string $calendarMonth = '';

    public int $calendarYear;

    // Booking form
    public bool $showBookingModal = false;

    #[Validate('required|date|after_or_equal:today')]
    public string $bookingDate = '';

    /**
     * End date for multi-day bookings. Only used when resource allows multi-day.
     */
    #[Validate('required|date|after_or_equal:bookingDate')]
    public string $endDate = '';

    #[Validate('required|date_format:H:i')]
    public string $startTime = '09:00';

    #[Validate('required|date_format:H:i|after:startTime')]
    public string $endTime = '10:00';

    #[Validate('nullable|string|max:500')]
    public string $notes = '';

    /**
     * Mount the component.
     */
    public function mount(Resource $resource): void
    {
        // Inactive resources should not be accessible to members
        if ($resource->status === \App\Enums\ResourceStatus::Inactive) {
            abort(404, 'Resource not found.');
        }

        $this->resource = $resource->load('images');

        // Initialize calendar to current month
        $now = now();
        $this->calendarMonth = $now->format('F');
        $this->calendarYear = $now->year;
        $this->selectedDate = $now->format('Y-m-d');
        $this->bookingDate = $now->format('Y-m-d');

        $this->buildCalendar($now->year, $now->month);
    }

    /**
     * Build calendar data for the given month.
     */
    public function buildCalendar(int $year, int $month): void
    {
        $date = now()->setYear($year)->setMonth($month)->startOfMonth();
        $this->calendarMonth = $date->format('F');
        $this->calendarYear = $year;

        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        // Get reservations for this month (extend range for multi-day display)
        $reservations = Reservation::getBlockingForResource(
            $this->resource->id,
            $startOfMonth->copy()->startOfWeek(),
            $endOfMonth->copy()->endOfWeek()->addDay()
        );

        // Build weeks array
        $weeks = [];
        $current = $startOfMonth->copy()->startOfWeek();

        while ($current <= $endOfMonth->copy()->endOfWeek()) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $dayDate = $current->copy();
                $isCurrentMonth = $dayDate->month === $month;
                $isPast = $dayDate->isPast() && ! $dayDate->isToday();
                $isToday = $dayDate->isToday();

                // Check if this day has reservations and categorize multi-day display
                $dayReservations = $reservations->filter(function ($r) use ($dayDate) {
                    return $r->starts_at->isSameDay($dayDate) ||
                           $r->ends_at->isSameDay($dayDate) ||
                           ($r->starts_at < $dayDate->endOfDay() && $r->ends_at > $dayDate->startOfDay());
                });

                // Determine multi-day position indicators for visual display
                $isMultiDayStart = false;
                $isMultiDayMiddle = false;
                $isMultiDayEnd = false;
                $hasMultiDay = false;

                foreach ($dayReservations as $r) {
                    $startDay = $r->starts_at->copy()->startOfDay();
                    $endDay = $r->ends_at->copy()->startOfDay();
                    $isMultiDay = $startDay->diffInDays($endDay) > 0;

                    if ($isMultiDay) {
                        $hasMultiDay = true;
                        if ($r->starts_at->isSameDay($dayDate)) {
                            $isMultiDayStart = true;
                        } elseif ($r->ends_at->isSameDay($dayDate)) {
                            $isMultiDayEnd = true;
                        } else {
                            $isMultiDayMiddle = true;
                        }
                    }
                }

                $week[] = [
                    'date' => $dayDate->format('Y-m-d'),
                    'day' => $dayDate->day,
                    'isCurrentMonth' => $isCurrentMonth,
                    'isPast' => $isPast,
                    'isToday' => $isToday,
                    'hasReservations' => $dayReservations->isNotEmpty(),
                    'reservationCount' => $dayReservations->count(),
                    // Multi-day reservation visual indicators
                    'hasMultiDay' => $hasMultiDay,
                    'isMultiDayStart' => $isMultiDayStart,
                    'isMultiDayMiddle' => $isMultiDayMiddle,
                    'isMultiDayEnd' => $isMultiDayEnd,
                ];

                $current->addDay();
            }
            $weeks[] = $week;
        }

        $this->calendarWeeks = $weeks;
    }

    /**
     * Navigate to previous month.
     */
    public function previousMonth(): void
    {
        $date = now()->setYear($this->calendarYear)->setMonth(
            array_search($this->calendarMonth, [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December',
            ]) + 1
        )->subMonth();

        $this->buildCalendar($date->year, $date->month);
    }

    /**
     * Navigate to next month.
     */
    public function nextMonth(): void
    {
        $date = now()->setYear($this->calendarYear)->setMonth(
            array_search($this->calendarMonth, [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December',
            ]) + 1
        )->addMonth();

        $this->buildCalendar($date->year, $date->month);
    }

    /**
     * Select a date on the calendar.
     */
    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
        $this->bookingDate = $date;
    }

    /**
     * Get reservations for the selected date.
     */
    #[Computed]
    public function selectedDateReservations(): \Illuminate\Support\Collection
    {
        if (! $this->selectedDate) {
            return collect();
        }

        $date = \Carbon\Carbon::parse($this->selectedDate);

        return Reservation::query()
            ->forResource($this->resource->id)
            ->blocking()
            ->onDate($date)
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * Open booking modal with selected date.
     */
    public function openBookingModal(?string $date = null): void
    {
        if ($date) {
            $this->bookingDate = $date;
            $this->selectedDate = $date;
        }

        // Initialize end date to same as start date for multi-day bookings
        $this->endDate = $this->bookingDate;
        $this->startTime = '09:00';
        $this->endTime = '10:00';
        $this->notes = '';
        $this->resetValidation();
        $this->showBookingModal = true;
    }

    /**
     * Close booking modal.
     */
    public function closeBookingModal(): void
    {
        $this->showBookingModal = false;
        $this->resetValidation();
    }

    /**
     * Submit a reservation request.
     */
    public function submitReservation(): void
    {
        $this->validate();

        // Determine the actual end date based on multi-day support
        $actualEndDate = $this->resource->allowsMultiDayBooking() ? $this->endDate : $this->bookingDate;

        $startsAt = \Carbon\Carbon::parse("{$this->bookingDate} {$this->startTime}");
        $endsAt = \Carbon\Carbon::parse("{$actualEndDate} {$this->endTime}");

        // Check if resource can be reserved (not under maintenance)
        if (! $this->resource->canBeReserved()) {
            $this->addError('bookingDate', 'This resource is currently unavailable for reservations.');

            return;
        }

        // Validate the time slot
        if ($startsAt->isPast()) {
            $this->addError('startTime', 'Start time must be in the future.');

            return;
        }

        // Check availability
        if (! Reservation::isSlotAvailable($this->resource->id, $startsAt, $endsAt)) {
            $this->addError('startTime', 'This time slot is not available. Please choose a different time.');

            return;
        }

        // Calculate booking duration in days
        $durationDays = $startsAt->copy()->startOfDay()->diffInDays($endsAt->copy()->startOfDay()) + 1;

        // Validate booking duration using the resource's helper method
        if (! $this->resource->isValidBookingDuration($durationDays)) {
            if ($this->resource->maxBookingDays() === 0) {
                $this->addError('endDate', 'This resource only allows single-day bookings.');
            } else {
                $this->addError('endDate', "Reservations cannot exceed {$this->resource->maxBookingDays()} days.");
            }

            return;
        }

        // Check advance booking days if set
        if ($this->resource->advance_booking_days) {
            $daysAhead = now()->startOfDay()->diffInDays($startsAt->copy()->startOfDay());
            if ($daysAhead > $this->resource->advance_booking_days) {
                $this->addError('bookingDate', "Bookings cannot be made more than {$this->resource->advance_booking_days} days in advance.");

                return;
            }
        }

        // Determine initial status based on resource settings
        $status = $this->resource->requires_approval
            ? ReservationStatus::Pending
            : ReservationStatus::Confirmed;

        $reservation = Reservation::create([
            'resource_id' => $this->resource->id,
            'user_id' => auth()->id(),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => $status,
            'notes' => $this->notes ?: null,
            'confirmed_at' => $status === ReservationStatus::Confirmed ? now() : null,
        ]);

        // Send notifications
        $this->sendReservationNotifications($reservation);

        $this->closeBookingModal();

        // Rebuild calendar to show new reservation
        $this->buildCalendar($this->calendarYear, array_search($this->calendarMonth, [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December',
        ]) + 1);

        $message = $status === ReservationStatus::Confirmed
            ? 'Your reservation has been confirmed!'
            : 'Your reservation request has been submitted and is pending approval.';

        session()->flash('success', $message);
    }

    /**
     * Send notification emails for a new reservation.
     */
    private function sendReservationNotifications(Reservation $reservation): void
    {
        $reservation->load(['resource', 'user']);

        // Notify the member if confirmed
        if ($reservation->isConfirmed()) {
            $reservation->user->notify(new ReservationConfirmed($reservation));
        }

        // Notify admins about new reservation
        User::admins()->approved()->each(function ($admin) use ($reservation) {
            $admin->notify(new NewReservationAlert($reservation));
        });
    }

    public function render()
    {
        return view('livewire.member.resource-detail')
            ->title("{$this->resource->name} - TribeTrip");
    }
}
