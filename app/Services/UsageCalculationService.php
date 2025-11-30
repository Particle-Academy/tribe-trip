<?php

namespace App\Services;

use App\Enums\PricingModel;
use App\Models\UsageLog;

/**
 * Service for calculating usage-based costs.
 *
 * Calculates duration, distance, and cost from usage log entries.
 */
class UsageCalculationService
{
    /**
     * Calculate usage metrics and cost for a usage log.
     *
     * @return array{duration_hours: float|null, distance_units: float|null, calculated_cost: float}
     */
    public function calculate(UsageLog $usageLog): array
    {
        $resource = $usageLog->resource;
        $duration = $this->calculateDuration($usageLog);
        $distance = $this->calculateDistance($usageLog);
        $cost = $this->calculateCost($usageLog, $duration, $distance);

        return [
            'duration_hours' => $duration,
            'distance_units' => $distance,
            'calculated_cost' => $cost,
        ];
    }

    /**
     * Calculate and update a usage log with metrics.
     */
    public function calculateAndUpdate(UsageLog $usageLog): bool
    {
        $metrics = $this->calculate($usageLog);

        return $usageLog->update($metrics);
    }

    /**
     * Calculate duration in hours between check-out and check-in.
     */
    public function calculateDuration(UsageLog $usageLog): ?float
    {
        if (! $usageLog->checked_out_at || ! $usageLog->checked_in_at) {
            return null;
        }

        $minutes = $usageLog->checked_out_at->diffInMinutes($usageLog->checked_in_at);

        return round($minutes / 60, 2);
    }

    /**
     * Calculate distance/units from start and end readings.
     */
    public function calculateDistance(UsageLog $usageLog): ?float
    {
        if ($usageLog->start_reading === null || $usageLog->end_reading === null) {
            return null;
        }

        $distance = $usageLog->end_reading - $usageLog->start_reading;

        // Ensure non-negative (handle odometer rollover edge case)
        return max(0, round($distance, 2));
    }

    /**
     * Calculate cost based on resource pricing model and usage.
     */
    public function calculateCost(UsageLog $usageLog, ?float $duration = null, ?float $distance = null): float
    {
        $resource = $usageLog->resource;

        if (! $resource) {
            return 0.00;
        }

        // Use provided values or calculate fresh
        $duration = $duration ?? $this->calculateDuration($usageLog);
        $distance = $distance ?? $this->calculateDistance($usageLog);

        return match ($resource->pricing_model) {
            PricingModel::FlatFee => (float) $resource->rate,
            PricingModel::PerUnit => $this->calculatePerUnitCost($resource, $duration, $distance),
            default => 0.00,
        };
    }

    /**
     * Calculate per-unit cost based on the pricing unit type.
     */
    private function calculatePerUnitCost($resource, ?float $duration, ?float $distance): float
    {
        $rate = (float) $resource->rate;
        $unit = $resource->pricing_unit;

        if (! $unit) {
            return 0.00;
        }

        // Determine which metric to use based on unit type
        $units = match ($unit->value) {
            'hour' => $duration ?? 0,
            'day' => $duration ? ceil($duration / 24) : 0, // Round up to full days
            'mile', 'kilometer' => $distance ?? 0,
            'trip' => 1, // Flat per-trip rate
            default => 0,
        };

        return round($rate * $units, 2);
    }

    /**
     * Estimate cost for a reservation (before actual usage).
     */
    public function estimateCost($resource, $startsAt, $endsAt): float
    {
        if ($resource->pricing_model === PricingModel::FlatFee) {
            return (float) $resource->rate;
        }

        // Estimate based on reserved time
        $duration = $startsAt->diffInMinutes($endsAt) / 60;

        return $this->calculatePerUnitCost($resource, $duration, null);
    }
}

