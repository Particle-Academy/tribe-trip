<?php

namespace App\Enums;

/**
 * Resource type enum.
 *
 * Defines the category of community resources.
 */
enum ResourceType: string
{
    case Vehicle = 'vehicle';
    case Equipment = 'equipment';
    case Space = 'space';
    case Other = 'other';

    /**
     * Get a human-readable label for the resource type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Vehicle => 'Vehicle',
            self::Equipment => 'Equipment',
            self::Space => 'Space',
            self::Other => 'Other',
        };
    }

    /**
     * Get an icon name for the resource type.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Vehicle => 'truck',
            self::Equipment => 'wrench-screwdriver',
            self::Space => 'home',
            self::Other => 'cube',
        };
    }

    /**
     * Get a color for display (colorblind-friendly).
     */
    public function color(): string
    {
        return match ($this) {
            self::Vehicle => 'blue',
            self::Equipment => 'amber',
            self::Space => 'teal',
            self::Other => 'zinc',
        };
    }
}

