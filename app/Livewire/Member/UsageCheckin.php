<?php

namespace App\Livewire\Member;

use App\Models\UsageLog;
use App\Services\UsageCalculationService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Member check-in component for ending resource usage.
 *
 * Captures ending meter reading and photo evidence, calculates usage.
 */
#[Layout('components.layouts.app')]
class UsageCheckin extends Component
{
    use WithFileUploads;

    #[Locked]
    public UsageLog $usageLog;

    #[Validate('nullable|numeric|min:0')]
    public ?string $endReading = null;

    #[Validate('nullable|image|max:5120')]
    public $endPhoto;

    #[Validate('nullable|string|max:500')]
    public string $notes = '';

    public bool $useManualEntry = false;

    /**
     * Mount the component.
     */
    public function mount(UsageLog $usageLog): void
    {
        $this->usageLog = $usageLog->load(['reservation', 'resource', 'user']);

        // Verify this is the user's usage log and it can be checked in
        if ($this->usageLog->user_id !== auth()->id()) {
            abort(403, 'This is not your usage log.');
        }

        if (! $this->usageLog->canCheckIn()) {
            session()->flash('error', 'This usage log cannot be checked in.');

            return;
        }

        // Pre-fill if we have a start reading, suggest the same value
        if ($this->usageLog->start_reading !== null) {
            $this->endReading = (string) $this->usageLog->start_reading;
        }
    }

    /**
     * Toggle manual entry mode.
     */
    public function toggleManualEntry(): void
    {
        $this->useManualEntry = ! $this->useManualEntry;
    }

    /**
     * Process the check-in.
     */
    public function checkin(): void
    {
        $this->validate();

        // Validate end reading is not less than start reading
        if ($this->usageLog->start_reading !== null && $this->endReading !== null) {
            if ((float) $this->endReading < (float) $this->usageLog->start_reading) {
                $this->addError('endReading', 'End reading cannot be less than start reading.');

                return;
            }
        }

        // Store photo if uploaded
        $photoPath = null;
        if ($this->endPhoto) {
            $photoPath = $this->endPhoto->store('usage-photos', 'public');
        }

        // Check in the usage log
        $this->usageLog->checkIn(
            now(),
            $this->endReading ? (float) $this->endReading : null,
            $photoPath,
            $this->notes ?: null
        );

        // Calculate and update cost
        $calculator = new UsageCalculationService;
        $calculator->calculateAndUpdate($this->usageLog->fresh());

        // Complete the reservation
        $this->usageLog->reservation->complete();

        session()->flash('success', 'Check-in complete! Your usage has been logged.');

        $this->redirect(route('member.reservations'), navigate: true);
    }

    public function render()
    {
        return view('livewire.member.usage-checkin')
            ->title('Check In - ' . $this->usageLog->resource->name);
    }
}
