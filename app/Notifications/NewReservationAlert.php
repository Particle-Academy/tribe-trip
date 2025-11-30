<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent to admins when a new reservation is created.
 *
 * Sent for reservations requiring approval or as FYI for instant bookings.
 */
class NewReservationAlert extends Notification implements ShouldQueue
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
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $resource = $this->reservation->resource;
        $member = $this->reservation->user;
        $requiresApproval = $this->reservation->isPending();

        $message = (new MailMessage)
            ->subject($requiresApproval
                ? "Reservation Pending Approval: {$resource->name}"
                : "New Reservation: {$resource->name}")
            ->greeting("Hello {$notifiable->name}!");

        if ($requiresApproval) {
            $message->line("A new reservation request needs your approval.");
        } else {
            $message->line("A new reservation has been confirmed.");
        }

        $message
            ->line("**Resource:** {$resource->name}")
            ->line("**Member:** {$member->name} ({$member->email})")
            ->line("**Date:** {$this->reservation->starts_at->format('l, F j, Y')}")
            ->line("**Time:** {$this->reservation->starts_at->format('g:i A')} - {$this->reservation->ends_at->format('g:i A')}");

        if ($this->reservation->notes) {
            $message->line("**Notes:** {$this->reservation->notes}");
        }

        if ($requiresApproval) {
            $message->action('Review Reservation', route('admin.members.show', $member));
        }

        return $message;
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
            'member_name' => $this->reservation->user->name,
            'starts_at' => $this->reservation->starts_at->toISOString(),
            'requires_approval' => $this->reservation->isPending(),
        ];
    }
}
