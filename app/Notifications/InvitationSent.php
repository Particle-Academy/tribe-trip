<?php

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent when an admin invites someone to join.
 *
 * Contains the invitation link and expiration information.
 */
class InvitationSent extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Invitation $invitation
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
        $greeting = $this->invitation->name
            ? "Hello {$this->invitation->name}!"
            : 'Hello!';

        $inviterName = $this->invitation->invitedBy->name;
        $expiresAt = $this->invitation->expires_at->format('F j, Y \a\t g:i A');

        return (new MailMessage)
            ->subject("You're Invited to Join TribeTrip!")
            ->greeting($greeting)
            ->line("{$inviterName} has invited you to join the TribeTrip community.")
            ->line('TribeTrip is a resource sharing platform for our community. Once you join, you can:')
            ->line('• Browse and reserve shared vehicles and equipment')
            ->line('• Track your usage and manage reservations')
            ->line('• Connect with other community members')
            ->action('Accept Invitation', $this->invitation->getUrl())
            ->line("This invitation expires on {$expiresAt}.")
            ->line('If you did not expect this invitation, you can safely ignore this email.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'invitation_sent',
            'invitation_id' => $this->invitation->id,
            'email' => $this->invitation->email,
        ];
    }
}
