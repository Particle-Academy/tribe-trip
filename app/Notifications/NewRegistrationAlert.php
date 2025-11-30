<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Admin notification for new user registrations.
 *
 * Sent to administrators when a new user registers and needs approval.
 */
class NewRegistrationAlert extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public User $registeredUser
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Registration Pending Approval - TribeTrip')
            ->greeting("Hello {$notifiable->name}!")
            ->line('A new user has registered and is awaiting approval.')
            ->line("**Applicant Details:**")
            ->line("• Name: {$this->registeredUser->name}")
            ->line("• Email: {$this->registeredUser->email}")
            ->line("• Registered: {$this->registeredUser->created_at->format('M j, Y g:i A')}")
            ->action('Review Registrations', url('/admin/approvals'))
            ->line('Please review this registration at your earliest convenience.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_registration_alert',
            'user_id' => $this->registeredUser->id,
            'user_name' => $this->registeredUser->name,
            'user_email' => $this->registeredUser->email,
        ];
    }
}
