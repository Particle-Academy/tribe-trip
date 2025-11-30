<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent to member when an invoice is generated/sent.
 *
 * Includes invoice summary and link to view details.
 */
class InvoiceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Invoice $invoice,
        public string $type = 'sent'
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Check user's notification preferences
        if (! $notifiable->getNotificationSetting('email_invoice_notifications')) {
            return [];
        }

        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return match ($this->type) {
            'sent' => $this->buildSentEmail($notifiable),
            'reminder' => $this->buildReminderEmail($notifiable),
            'overdue' => $this->buildOverdueEmail($notifiable),
            default => $this->buildSentEmail($notifiable),
        };
    }

    /**
     * Build email for newly sent invoice.
     */
    protected function buildSentEmail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Your Invoice: {$this->invoice->invoice_number}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("A new invoice has been generated for your resource usage.")
            ->line("**Invoice Details:**")
            ->line("📋 Invoice #: {$this->invoice->invoice_number}")
            ->line("📅 Billing Period: {$this->invoice->billing_period}")
            ->line("💵 Total Amount: {$this->invoice->formatted_total}")
            ->when($this->invoice->due_date, fn ($mail) => $mail->line("📆 Due Date: {$this->invoice->due_date->format('F j, Y')}"))
            ->line("**Usage Summary:**")
            ->line($this->buildItemSummary())
            ->action('View Invoice Details', route('member.reservations'))
            ->line('Thank you for being part of our community!');
    }

    /**
     * Build email for payment reminder.
     */
    protected function buildReminderEmail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Payment Reminder: {$this->invoice->invoice_number}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("This is a friendly reminder that your invoice is due soon.")
            ->line("**Invoice Details:**")
            ->line("📋 Invoice #: {$this->invoice->invoice_number}")
            ->line("💵 Amount Due: {$this->invoice->formatted_total}")
            ->line("📆 Due Date: {$this->invoice->due_date?->format('F j, Y')}")
            ->action('View Invoice', route('member.reservations'))
            ->line('Please contact us if you have any questions about your bill.');
    }

    /**
     * Build email for overdue invoice.
     */
    protected function buildOverdueEmail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Payment Overdue: {$this->invoice->invoice_number}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your invoice is now past due. Please arrange payment as soon as possible.")
            ->line("**Invoice Details:**")
            ->line("📋 Invoice #: {$this->invoice->invoice_number}")
            ->line("💵 Amount Due: {$this->invoice->formatted_total}")
            ->line("📆 Original Due Date: {$this->invoice->due_date?->format('F j, Y')}")
            ->action('View Invoice', route('member.reservations'))
            ->line('If you have already made payment, please disregard this notice.')
            ->line('For questions or payment arrangements, please contact an administrator.');
    }

    /**
     * Build a summary of invoice items.
     */
    protected function buildItemSummary(): string
    {
        $items = $this->invoice->items;
        $count = $items->count();

        if ($count === 0) {
            return 'No items';
        }

        if ($count <= 3) {
            return $items->map(fn ($item) => "• {$item->description}: {$item->formatted_amount}")->implode("\n");
        }

        $firstThree = $items->take(3)->map(fn ($item) => "• {$item->description}: {$item->formatted_amount}")->implode("\n");
        $remaining = $count - 3;

        return $firstThree . "\n• ...and {$remaining} more item(s)";
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'total' => $this->invoice->total,
            'type' => $this->type,
        ];
    }
}
