<?php

namespace App\Livewire\Admin;

use App\Models\Invitation;
use App\Notifications\InvitationSent;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Admin component for creating and sending invitations.
 *
 * Allows admins to invite new members via email or shareable link.
 */
#[Layout('components.layouts.admin')]
#[Title('Create Invitation - TribeTrip Admin')]
class CreateInvitation extends Component
{
    #[Validate('required|email|unique:invitations,email,NULL,id,status,pending')]
    public string $email = '';

    #[Validate('nullable|string|max:255')]
    public string $name = '';

    #[Validate('required|integer|min:1|max:30')]
    public int $expiresInDays = 7;

    public bool $sendEmail = true;

    public ?Invitation $createdInvitation = null;

    public bool $showSuccess = false;

    /**
     * Create the invitation.
     */
    public function create(): void
    {
        $this->validate();

        $invitation = Invitation::create([
            'email' => $this->email,
            'name' => $this->name ?: null,
            'expires_at' => now()->addDays($this->expiresInDays),
            'invited_by' => auth()->id(),
        ]);

        if ($this->sendEmail) {
            // Send invitation email using anonymous notifiable
            Notification::route('mail', $invitation->email)
                ->notify(new InvitationSent($invitation));
        }

        $this->createdInvitation = $invitation;
        $this->showSuccess = true;

        // Reset form for next invitation
        $this->reset(['email', 'name', 'expiresInDays', 'sendEmail']);
        $this->expiresInDays = 7;
        $this->sendEmail = true;
    }

    /**
     * Copy invitation link to clipboard (handled in JS).
     */
    public function copyLink(): void
    {
        // This is handled by Alpine.js in the view
        // Method exists for potential server-side tracking
    }

    /**
     * Create another invitation.
     */
    public function createAnother(): void
    {
        $this->showSuccess = false;
        $this->createdInvitation = null;
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Please enter an email address.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'An active invitation already exists for this email.',
            'expiresInDays.min' => 'Invitation must be valid for at least 1 day.',
            'expiresInDays.max' => 'Invitation cannot be valid for more than 30 days.',
        ];
    }

    public function render()
    {
        return view('livewire.admin.create-invitation');
    }
}
