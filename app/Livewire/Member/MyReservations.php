<?php

namespace App\Livewire\Member;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Member reservations dashboard component.
 *
 * Shows upcoming and past reservations with cancellation ability.
 */
#[Layout('components.layouts.app')]
#[Title('My Reservations - TribeTrip')]
class MyReservations extends Component
{
    use WithPagination;

    #[Url]
    public string $filter = 'upcoming';

    // Cancel modal
    public bool $showCancelModal = false;

    public ?Reservation $reservationToCancel = null;

    #[Validate('nullable|string|max:500')]
    public string $cancellationReason = '';

    /**
     * Reset pagination when filter changes.
     */
    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Open cancel confirmation modal.
     */
    public function confirmCancel(int $reservationId): void
    {
        $reservation = Reservation::with('resource')
            ->where('user_id', auth()->id())
            ->findOrFail($reservationId);

        if (! $reservation->canBeCancelled()) {
            session()->flash('error', 'This reservation cannot be cancelled.');

            return;
        }

        $this->reservationToCancel = $reservation;
        $this->cancellationReason = '';
        $this->showCancelModal = true;
    }

    /**
     * Close cancel modal.
     */
    public function closeCancelModal(): void
    {
        $this->showCancelModal = false;
        $this->reservationToCancel = null;
        $this->cancellationReason = '';
    }

    /**
     * Cancel the reservation.
     */
    public function cancelReservation(): void
    {
        if (! $this->reservationToCancel || ! $this->reservationToCancel->canBeCancelled()) {
            session()->flash('error', 'This reservation cannot be cancelled.');
            $this->closeCancelModal();

            return;
        }

        $resourceName = $this->reservationToCancel->resource->name;

        $this->reservationToCancel->cancel(
            $this->cancellationReason ?: null,
            auth()->id()
        );

        $this->closeCancelModal();
        session()->flash('success', "Your reservation for {$resourceName} has been cancelled.");
    }

    public function render()
    {
        $query = Reservation::query()
            ->with(['resource', 'resource.images'])
            ->forUser(auth()->id());

        $reservations = match ($this->filter) {
            'upcoming' => $query->clone()
                ->upcoming()
                ->orderBy('starts_at')
                ->paginate(10),
            'past' => $query->clone()
                ->past()
                ->orderByDesc('starts_at')
                ->paginate(10),
            'cancelled' => $query->clone()
                ->withStatus(ReservationStatus::Cancelled)
                ->orderByDesc('cancelled_at')
                ->paginate(10),
            default => $query->clone()
                ->orderByDesc('created_at')
                ->paginate(10),
        };

        // Get counts for filter tabs
        $counts = [
            'upcoming' => Reservation::forUser(auth()->id())->upcoming()->count(),
            'past' => Reservation::forUser(auth()->id())->past()->count(),
            'cancelled' => Reservation::forUser(auth()->id())->withStatus(ReservationStatus::Cancelled)->count(),
        ];

        return view('livewire.member.my-reservations', [
            'reservations' => $reservations,
            'counts' => $counts,
        ]);
    }
}
