<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent to member when their reservation is confirmed.
 *
 * Sent for both instant confirmations and admin-approved reservations.
 */
class ReservationConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Reservation $reservation
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Check user's notification preferences
        if (! $notifiable->getNotificationSetting('email_reservation_confirmations')) {
            return [];
        }

        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $resource = $this->reservation->resource;

        return (new MailMessage)
            ->subject("Reservation Confirmed: {$resource->name}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Your reservation for **{$resource->name}** has been confirmed.")
            ->line("**Details:**")
            ->line("📅 Date: {$this->reservation->starts_at->format('l, F j, Y')}")
            ->line("⏰ Time: {$this->reservation->starts_at->format('g:i A')} - {$this->reservation->ends_at->format('g:i A')}")
            ->line("💵 Cost: {$resource->formatted_price}")
            ->action('View My Reservations', route('member.reservations'))
            ->line('Thank you for using TribeTrip!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'reservation_id' => $this->reservation->id,
            'resource_name' => $this->reservation->resource->name,
            'starts_at' => $this->reservation->starts_at->toISOString(),
            'ends_at' => $this->reservation->ends_at->toISOString(),
        ];
    }
}
