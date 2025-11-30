<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Registration confirmation notification.
 *
 * Sent to users when they register, confirming receipt and explaining the approval process.
 */
class RegistrationReceived extends Notification implements ShouldQueue
{
    use Queueable;

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
            ->subject('Registration Received - TribeTrip')
            ->greeting("Hello {$notifiable->name}!")
            ->line('Thank you for registering with TribeTrip. We have received your application to join our community.')
            ->line('**What happens next?**')
            ->line('• An administrator will review your registration')
            ->line('• You will receive an email notification once a decision has been made')
            ->line('• Once approved, you can log in and start accessing shared community resources')
            ->line('This process typically takes 1-2 business days.')
            ->action('Return to TribeTrip', url('/'))
            ->line('If you have any questions, please contact your community administrator.')
            ->salutation('Welcome to the community!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'registration_received',
            'message' => 'Your registration has been received and is pending approval.',
        ];
    }
}
