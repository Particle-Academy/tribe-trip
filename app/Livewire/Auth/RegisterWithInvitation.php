<?php

namespace App\Livewire\Auth;

use App\Enums\UserStatus;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Registration component for invited users.
 *
 * Users registering via invitation are automatically approved.
 */
#[Layout('components.layouts.guest')]
#[Title('Accept Invitation - TribeTrip')]
class RegisterWithInvitation extends Component
{
    #[Locked]
    public ?Invitation $invitation = null;

    #[Locked]
    public bool $isValid = false;

    #[Locked]
    public string $invalidReason = '';

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|min:8|confirmed')]
    public string $password = '';

    #[Validate('required|string')]
    public string $password_confirmation = '';

    #[Validate('nullable|string|max:20')]
    public string $phone = '';

    /**
     * Mount the component with the invitation token.
     */
    public function mount(string $token): void
    {
        $this->invitation = Invitation::findByToken($token);

        if (! $this->invitation) {
            $this->invalidReason = 'This invitation link is invalid or has been removed.';

            return;
        }

        if ($this->invitation->isAccepted()) {
            $this->invalidReason = 'This invitation has already been used.';

            return;
        }

        if ($this->invitation->isRevoked()) {
            $this->invalidReason = 'This invitation has been revoked.';

            return;
        }

        if ($this->invitation->isExpired()) {
            $this->invalidReason = 'This invitation has expired.';

            return;
        }

        // Pre-fill name if provided in invitation
        if ($this->invitation->name) {
            $this->name = $this->invitation->name;
        }

        $this->isValid = true;
    }

    /**
     * Complete registration with the invitation.
     */
    public function register(): void
    {
        if (! $this->isValid || ! $this->invitation) {
            return;
        }

        $this->validate();

        // Check if email is already registered
        if (User::where('email', $this->invitation->email)->exists()) {
            $this->addError('email', 'An account with this email already exists.');

            return;
        }

        // Create user with approved status (bypasses approval queue)
        $user = User::create([
            'name' => $this->name,
            'email' => $this->invitation->email,
            'phone' => $this->phone ?: null,
            'password' => $this->password,
            'status' => UserStatus::Approved,
            'status_changed_at' => now(),
        ]);

        // Mark invitation as accepted
        $this->invitation->markAsAccepted($user);

        event(new Registered($user));

        // Log the user in immediately
        Auth::login($user);

        session()->regenerate();

        $this->redirect(route('dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.register-with-invitation');
    }
}
