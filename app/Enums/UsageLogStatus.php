<?php

namespace App\Enums;

/**
 * Usage log status enum.
 *
 * Tracks the state of a resource usage session.
 */
enum UsageLogStatus: string
{
    case CheckedOut = 'checked_out';
    case Completed = 'completed';
    case Disputed = 'disputed';
    case Verified = 'verified';

    /**
     * Get a human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::CheckedOut => 'Checked Out',
            self::Completed => 'Completed',
            self::Disputed => 'Disputed',
            self::Verified => 'Verified',
        };
    }

    /**
     * Get the status color for UI display (colorblind-friendly).
     */
    public function color(): string
    {
        return match ($this) {
            self::CheckedOut => 'blue',
            self::Completed => 'amber',
            self::Disputed => 'red',
            self::Verified => 'green',
        };
    }

    /**
     * Check if the usage is still in progress.
     */
    public function isInProgress(): bool
    {
        return $this === self::CheckedOut;
    }

    /**
     * Check if the usage can be billed.
     */
    public function isBillable(): bool
    {
        return in_array($this, [self::Completed, self::Verified]);
    }
}

