<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent when a user's registration is rejected.
 *
 * Informs the user their application was not approved.
 */
class RegistrationRejected extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public ?string $reason = null
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
        $message = (new MailMessage)
            ->subject('TribeTrip Registration Update')
            ->greeting("Hello {$notifiable->name},")
            ->line('Thank you for your interest in joining our community.')
            ->line('After reviewing your registration, we are unable to approve your application at this time.');

        if ($this->reason) {
            $message->line("**Reason:** {$this->reason}");
        }

        return $message
            ->line('If you believe this was in error or have questions, please contact your community administrator.')
            ->salutation('Best regards,')
            ->line('The TribeTrip Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'registration_rejected',
            'message' => 'Your registration has been rejected.',
            'reason' => $this->reason,
        ];
    }
}
