<?php

namespace App\Livewire\Auth;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Pending approval page shown after registration.
 *
 * Displays status message while user awaits admin approval.
 */
#[Layout('components.layouts.guest')]
#[Title('Pending Approval - TribeTrip')]
class PendingApproval extends Component
{
    public function render()
    {
        return view('livewire.auth.pending-approval');
    }
}

