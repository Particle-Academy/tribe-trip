<?php

namespace App\Livewire\Member;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\UsageLog;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Member check-out component for starting resource usage.
 *
 * Captures starting meter reading and photo evidence.
 */
#[Layout('components.layouts.app')]
class UsageCheckout extends Component
{
    use WithFileUploads;

    #[Locked]
    public Reservation $reservation;

    #[Validate('nullable|numeric|min:0')]
    public ?string $startReading = null;

    #[Validate('nullable|image|max:5120')]
    public $startPhoto;

    #[Validate('nullable|string|max:500')]
    public string $notes = '';

    public bool $useManualEntry = false;

    /**
     * Mount the component.
     */
    public function mount(Reservation $reservation): void
    {
        $this->reservation = $reservation->load(['resource', 'user']);

        // Verify this is the user's reservation and it can be checked out
        if ($this->reservation->user_id !== auth()->id()) {
            abort(403, 'This is not your reservation.');
        }

        if (! $this->canCheckOut()) {
            session()->flash('error', 'This reservation cannot be checked out.');

            return;
        }
    }

    /**
     * Check if the reservation can be checked out.
     */
    public function canCheckOut(): bool
    {
        // Must be confirmed
        if (! $this->reservation->isConfirmed()) {
            return false;
        }

        // Must not already have a usage log
        if ($this->reservation->usageLog()->exists()) {
            return false;
        }

        // Must be within the reservation window (or slightly before)
        $now = now();
        $startsAt = $this->reservation->starts_at;
        $endsAt = $this->reservation->ends_at;

        // Allow check-out up to 30 minutes early or anytime during reservation
        return $now->greaterThanOrEqualTo($startsAt->subMinutes(30)) && $now->lessThan($endsAt);
    }

    /**
     * Toggle manual entry mode.
     */
    public function toggleManualEntry(): void
    {
        $this->useManualEntry = ! $this->useManualEntry;
    }

    /**
     * Process the check-out.
     */
    public function checkout(): void
    {
        $this->validate();

        // Store photo if uploaded
        $photoPath = null;
        if ($this->startPhoto) {
            $photoPath = $this->startPhoto->store('usage-photos', 'public');
        }

        // Create usage log
        UsageLog::create([
            'reservation_id' => $this->reservation->id,
            'user_id' => auth()->id(),
            'resource_id' => $this->reservation->resource_id,
            'checked_out_at' => now(),
            'start_reading' => $this->startReading ? (float) $this->startReading : null,
            'start_photo_path' => $photoPath,
            'start_notes' => $this->notes ?: null,
        ]);

        // Update reservation status
        $this->reservation->checkOut();

        session()->flash('success', 'Check-out successful! Enjoy your time with ' . $this->reservation->resource->name . '.');

        $this->redirect(route('member.reservations'), navigate: true);
    }

    public function render()
    {
        return view('livewire.member.usage-checkout')
            ->title('Check Out - ' . $this->reservation->resource->name);
    }
}
