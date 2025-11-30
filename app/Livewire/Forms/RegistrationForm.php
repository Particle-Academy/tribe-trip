<?php

namespace App\Livewire\Forms;

use App\Enums\UserStatus;
use App\Models\User;
use App\Notifications\NewRegistrationAlert;
use App\Notifications\RegistrationReceived;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Validate;
use Livewire\Form;

/**
 * Form object for user registration.
 *
 * Handles validation and creation of new users with pending approval status.
 */
class RegistrationForm extends Form
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email|unique:users,email')]
    public string $email = '';

    #[Validate('nullable|string|max:20')]
    public string $phone = '';

    #[Validate('required|string|min:8|confirmed')]
    public string $password = '';

    #[Validate('required|string')]
    public string $password_confirmation = '';

    /**
     * Create a new user with pending approval status.
     *
     * Sends registration confirmation email to the user and alerts admins.
     */
    public function store(): User
    {
        $this->validate();

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone ?: null,
            'password' => $this->password,
            'status' => UserStatus::Pending,
        ]);

        event(new Registered($user));

        // Send registration confirmation email to user
        $user->notify(new RegistrationReceived);

        // Alert all admins about new registration
        $admins = User::admins()->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new NewRegistrationAlert($user));
        }

        return $user;
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please enter your full name.',
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already registered.',
            'password.required' => 'Please create a password.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Passwords do not match.',
        ];
    }
}

