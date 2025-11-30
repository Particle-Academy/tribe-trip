<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Resource;
use App\Models\UsageLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitPrice = fake()->randomFloat(2, 5, 50);
        $quantity = fake()->randomFloat(2, 1, 10);
        $amount = round($unitPrice * $quantity, 2);

        return [
            'invoice_id' => Invoice::factory(),
            'usage_log_id' => null,
            'resource_id' => Resource::factory(),
            'description' => fake()->sentence(4),
            'quantity' => $quantity,
            'unit' => fake()->randomElement(['hr', 'mi', 'day', null]),
            'unit_price' => $unitPrice,
            'amount' => $amount,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Item Type States
    |--------------------------------------------------------------------------
    */

    /**
     * Create item from a usage log.
     */
    public function fromUsageLog(UsageLog $usageLog): static
    {
        $resource = $usageLog->resource;
        $quantity = $usageLog->distance_units ?? $usageLog->duration_hours ?? 1;
        $unitPrice = (float) $resource->rate;
        $amount = (float) $usageLog->calculated_cost ?: ($quantity * $unitPrice);

        $description = $resource->name;
        if ($usageLog->checked_out_at) {
            $description .= ' - ' . $usageLog->checked_out_at->format('M j, Y');
        }

        return $this->state(fn () => [
            'usage_log_id' => $usageLog->id,
            'resource_id' => $resource->id,
            'description' => $description,
            'quantity' => $quantity,
            'unit' => $resource->pricing_unit?->abbreviation(),
            'unit_price' => $unitPrice,
            'amount' => $amount,
        ]);
    }

    /**
     * Create a manual entry (no usage log).
     */
    public function manual(string $description, float $amount): static
    {
        return $this->state(fn () => [
            'usage_log_id' => null,
            'resource_id' => null,
            'description' => $description,
            'quantity' => 1,
            'unit' => null,
            'unit_price' => $amount,
            'amount' => $amount,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Relationship States
    |--------------------------------------------------------------------------
    */

    /**
     * Set for a specific invoice.
     */
    public function forInvoice(Invoice $invoice): static
    {
        return $this->state(fn () => [
            'invoice_id' => $invoice->id,
        ]);
    }

    /**
     * Set for a specific resource.
     */
    public function forResource(Resource $resource): static
    {
        return $this->state(fn () => [
            'resource_id' => $resource->id,
            'description' => $resource->name,
            'unit_price' => $resource->rate,
        ]);
    }

    /**
     * Configure as hourly charge.
     */
    public function hourly(float $hours, float $rate): static
    {
        return $this->state(fn () => [
            'quantity' => $hours,
            'unit' => 'hr',
            'unit_price' => $rate,
            'amount' => round($hours * $rate, 2),
        ]);
    }

    /**
     * Configure as mileage charge.
     */
    public function mileage(float $miles, float $rate): static
    {
        return $this->state(fn () => [
            'quantity' => $miles,
            'unit' => 'mi',
            'unit_price' => $rate,
            'amount' => round($miles * $rate, 2),
        ]);
    }
}
