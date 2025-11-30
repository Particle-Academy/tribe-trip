<?php

namespace App\Enums;

/**
 * Invitation status enum.
 *
 * Tracks the lifecycle of community invitations.
 */
enum InvitationStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Revoked = 'revoked';
    case Expired = 'expired';

    /**
     * Get a human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Accepted => 'Accepted',
            self::Revoked => 'Revoked',
            self::Expired => 'Expired',
        };
    }

    /**
     * Get the status color for UI display (colorblind-friendly).
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Accepted => 'green',
            self::Revoked => 'red',
            self::Expired => 'zinc',
        };
    }

    /**
     * Check if the invitation can still be used.
     */
    public function isUsable(): bool
    {
        return $this === self::Pending;
    }
}

