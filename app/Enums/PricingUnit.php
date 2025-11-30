<?php

namespace App\Enums;

/**
 * Resource pricing unit enum.
 *
 * Defines the unit of measurement for per-unit pricing.
 */
enum PricingUnit: string
{
    case Hour = 'hour';
    case Day = 'day';
    case Mile = 'mile';
    case Kilometer = 'kilometer';
    case Trip = 'trip';

    /**
     * Get a human-readable label for the unit.
     */
    public function label(): string
    {
        return match ($this) {
            self::Hour => 'Hour',
            self::Day => 'Day',
            self::Mile => 'Mile',
            self::Kilometer => 'Kilometer',
            self::Trip => 'Trip',
        };
    }

    /**
     * Get the plural label.
     */
    public function pluralLabel(): string
    {
        return match ($this) {
            self::Hour => 'Hours',
            self::Day => 'Days',
            self::Mile => 'Miles',
            self::Kilometer => 'Kilometers',
            self::Trip => 'Trips',
        };
    }

    /**
     * Get the short abbreviation.
     */
    public function abbreviation(): string
    {
        return match ($this) {
            self::Hour => 'hr',
            self::Day => 'day',
            self::Mile => 'mi',
            self::Kilometer => 'km',
            self::Trip => 'trip',
        };
    }
}

