<?php

namespace App\Livewire\Auth;

use App\Enums\UserStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Login component for community members.
 *
 * Only approved users can log in.
 */
#[Layout('components.layouts.guest')]
#[Title('Login - TribeTrip')]
class Login extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    /**
     * Handle login attempt.
     */
    public function login(): void
    {
        $this->validate();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            throw ValidationException::withMessages([
                'email' => __('These credentials do not match our records.'),
            ]);
        }

        $user = Auth::user();

        // Check if user is approved
        if ($user->status === UserStatus::Pending) {
            Auth::logout();
            $this->redirect(route('register.pending'), navigate: true);

            return;
        }

        if ($user->status === UserStatus::Rejected) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => __('Your registration was not approved. Please contact an administrator.'),
            ]);
        }

        session()->regenerate();

        $this->redirect(route('dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}

