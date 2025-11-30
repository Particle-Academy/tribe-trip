<?php

namespace App\Enums;

/**
 * Invoice status enum.
 *
 * Tracks the lifecycle state of an invoice from draft to paid.
 */
enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Voided = 'voided';

    /**
     * Get a human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sent => 'Sent',
            self::Paid => 'Paid',
            self::Overdue => 'Overdue',
            self::Voided => 'Voided',
        };
    }

    /**
     * Get the status color for UI display (colorblind-friendly).
     */
    public function color(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::Sent => 'blue',
            self::Paid => 'green',
            self::Overdue => 'amber',
            self::Voided => 'red',
        };
    }

    /**
     * Check if the invoice can be edited.
     */
    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    /**
     * Check if the invoice can be sent.
     */
    public function canBeSent(): bool
    {
        return $this === self::Draft;
    }

    /**
     * Check if the invoice can be marked as paid.
     */
    public function canBeMarkedPaid(): bool
    {
        return in_array($this, [self::Sent, self::Overdue]);
    }

    /**
     * Check if the invoice can be voided.
     */
    public function canBeVoided(): bool
    {
        return in_array($this, [self::Draft, self::Sent, self::Overdue]);
    }
}
