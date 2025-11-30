<?php

namespace App\Enums;

/**
 * Resource pricing model enum.
 *
 * Defines how a resource is priced: flat fee or per unit of usage.
 */
enum PricingModel: string
{
    case FlatFee = 'flat_fee';
    case PerUnit = 'per_unit';

    /**
     * Get a human-readable label for the pricing model.
     */
    public function label(): string
    {
        return match ($this) {
            self::FlatFee => 'Flat Fee',
            self::PerUnit => 'Per Unit',
        };
    }

    /**
     * Get a description of the pricing model.
     */
    public function description(): string
    {
        return match ($this) {
            self::FlatFee => 'One-time charge per reservation',
            self::PerUnit => 'Charge based on usage (hours, miles, etc.)',
        };
    }

    /**
     * Check if this model requires a unit type.
     */
    public function requiresUnit(): bool
    {
        return $this === self::PerUnit;
    }
}

