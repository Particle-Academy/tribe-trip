<?php

namespace App\Enums;

/**
 * Reservation status enum.
 *
 * Tracks the lifecycle of a resource reservation.
 */
enum ReservationStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case CheckedOut = 'checked_out';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * Get a human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending Approval',
            self::Confirmed => 'Confirmed',
            self::CheckedOut => 'Checked Out',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Get the status color for UI display (colorblind-friendly).
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Confirmed => 'blue',
            self::CheckedOut => 'indigo',
            self::Completed => 'green',
            self::Cancelled => 'zinc',
        };
    }

    /**
     * Check if the reservation is active (not completed or cancelled).
     */
    public function isActive(): bool
    {
        return ! in_array($this, [self::Completed, self::Cancelled]);
    }

    /**
     * Check if the reservation can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this, [self::Pending, self::Confirmed]);
    }

    /**
     * Check if the reservation blocks calendar availability.
     */
    public function blocksAvailability(): bool
    {
        return in_array($this, [self::Pending, self::Confirmed, self::CheckedOut]);
    }

    /**
     * Get statuses that count as "upcoming" reservations.
     */
    public static function upcomingStatuses(): array
    {
        return [self::Pending, self::Confirmed];
    }

    /**
     * Get statuses that block calendar slots.
     */
    public static function blockingStatuses(): array
    {
        return [self::Pending, self::Confirmed, self::CheckedOut];
    }
}

