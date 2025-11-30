<?php

namespace App\Livewire\Auth;

use App\Livewire\Forms\RegistrationForm;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Registration component for new community members.
 *
 * Users register with pending status and await admin approval.
 */
#[Layout('components.layouts.guest')]
#[Title('Register - TribeTrip')]
class Register extends Component
{
    public RegistrationForm $form;

    /**
     * Handle form submission and create new user.
     */
    public function register(): void
    {
        $user = $this->form->store();

        // Redirect to pending approval page instead of logging in
        $this->redirect(route('register.pending'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
}

