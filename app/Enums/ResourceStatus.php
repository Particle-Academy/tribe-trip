<?php

namespace App\Enums;

/**
 * Resource availability status enum.
 *
 * Tracks whether a resource is available for reservations.
 */
enum ResourceStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Maintenance = 'maintenance';

    /**
     * Get a human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Maintenance => 'Maintenance',
        };
    }

    /**
     * Get the status color for UI display (colorblind-friendly).
     */
    public function color(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::Inactive => 'zinc',
            self::Maintenance => 'amber',
        };
    }

    /**
     * Check if the resource can be reserved.
     */
    public function canBeReserved(): bool
    {
        return $this === self::Active;
    }
}

