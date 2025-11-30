<?php

namespace App\Enums;

/**
 * User approval status enum.
 *
 * Tracks the registration workflow state for community members.
 */
enum UserStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Suspended = 'suspended';

    /**
     * Get a human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending Approval',
            self::Approved => 'Active',
            self::Rejected => 'Rejected',
            self::Suspended => 'Suspended',
        };
    }

    /**
     * Get the status color for UI display (colorblind-friendly).
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Approved => 'green',
            self::Rejected => 'red',
            self::Suspended => 'zinc',
        };
    }

    /**
     * Check if the user can access the application.
     */
    public function canAccessApp(): bool
    {
        return $this === self::Approved;
    }

    /**
     * Get all active member statuses (for directory).
     */
    public static function activeStatuses(): array
    {
        return [self::Approved, self::Suspended];
    }
}
