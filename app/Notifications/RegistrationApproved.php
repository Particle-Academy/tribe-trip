<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent when a user's registration is approved.
 *
 * Informs the user they can now log in and access the community.
 */
class RegistrationApproved extends Notification implements ShouldQueue
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
            ->subject('Welcome to TribeTrip - Registration Approved!')
            ->greeting("Congratulations, {$notifiable->name}!")
            ->line('Great news! Your registration has been approved.')
            ->line('You can now log in and start accessing our community resources.')
            ->line('**What you can do now:**')
            ->line('• Browse available vehicles and equipment')
            ->line('• Make reservations for community resources')
            ->line('• View your usage history and invoices')
            ->action('Log In Now', url('/login'))
            ->line('Welcome to the TribeTrip community!')
            ->salutation('See you soon!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'registration_approved',
            'message' => 'Your registration has been approved.',
        ];
    }
}
