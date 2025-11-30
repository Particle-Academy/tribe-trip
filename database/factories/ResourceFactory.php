<?php

namespace Database\Factories;

use App\Enums\PricingModel;
use App\Enums\PricingUnit;
use App\Enums\ResourceStatus;
use App\Enums\ResourceType;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Resource>
 */
class ResourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(ResourceType::cases());
        $pricingModel = fake()->randomElement(PricingModel::cases());

        return [
            'name' => $this->getNameForType($type),
            'description' => fake()->paragraph(),
            'type' => $type,
            'status' => ResourceStatus::Active,
            'pricing_model' => $pricingModel,
            'rate' => fake()->randomFloat(2, 5, 100),
            'pricing_unit' => $pricingModel === PricingModel::PerUnit
                ? fake()->randomElement(PricingUnit::cases())
                : null,
            'requires_approval' => fake()->boolean(20),
            'max_reservation_days' => fake()->optional()->numberBetween(1, 14),
            'advance_booking_days' => fake()->optional()->numberBetween(7, 60),
            'created_by' => null,
        ];
    }

    /**
     * Generate an appropriate name based on resource type.
     */
    private function getNameForType(ResourceType $type): string
    {
        return match ($type) {
            ResourceType::Vehicle => fake()->randomElement([
                'Community Van',
                'Pickup Truck',
                'Club Sedan',
                'Moving Van',
                'ATV',
                'Golf Cart',
            ]),
            ResourceType::Equipment => fake()->randomElement([
                'Pressure Washer',
                'Lawn Mower',
                'Chainsaw',
                'Generator',
                'Trailer',
                'Tool Set',
            ]),
            ResourceType::Space => fake()->randomElement([
                'Community Room',
                'Meeting Space',
                'Workshop Area',
                'Outdoor Pavilion',
            ]),
            ResourceType::Other => fake()->randomElement([
                'Projector',
                'Sound System',
                'Tables & Chairs',
                'Camping Gear',
            ]),
        };
    }

    /*
    |--------------------------------------------------------------------------
    | State Modifiers
    |--------------------------------------------------------------------------
    */

    /**
     * Configure as a vehicle.
     */
    public function vehicle(): static
    {
        return $this->state(fn () => [
            'type' => ResourceType::Vehicle,
            'name' => $this->getNameForType(ResourceType::Vehicle),
        ]);
    }

    /**
     * Configure as equipment.
     */
    public function equipment(): static
    {
        return $this->state(fn () => [
            'type' => ResourceType::Equipment,
            'name' => $this->getNameForType(ResourceType::Equipment),
        ]);
    }

    /**
     * Configure as a space.
     */
    public function space(): static
    {
        return $this->state(fn () => [
            'type' => ResourceType::Space,
            'name' => $this->getNameForType(ResourceType::Space),
        ]);
    }

    /**
     * Configure with flat fee pricing.
     */
    public function flatFee(float $rate = 25.00): static
    {
        return $this->state(fn () => [
            'pricing_model' => PricingModel::FlatFee,
            'rate' => $rate,
            'pricing_unit' => null,
        ]);
    }

    /**
     * Configure with per-hour pricing.
     */
    public function perHour(float $rate = 10.00): static
    {
        return $this->state(fn () => [
            'pricing_model' => PricingModel::PerUnit,
            'rate' => $rate,
            'pricing_unit' => PricingUnit::Hour,
        ]);
    }

    /**
     * Configure with per-day pricing.
     */
    public function perDay(float $rate = 50.00): static
    {
        return $this->state(fn () => [
            'pricing_model' => PricingModel::PerUnit,
            'rate' => $rate,
            'pricing_unit' => PricingUnit::Day,
        ]);
    }

    /**
     * Configure with per-mile pricing.
     */
    public function perMile(float $rate = 0.50): static
    {
        return $this->state(fn () => [
            'pricing_model' => PricingModel::PerUnit,
            'rate' => $rate,
            'pricing_unit' => PricingUnit::Mile,
        ]);
    }

    /**
     * Configure as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn () => [
            'status' => ResourceStatus::Inactive,
        ]);
    }

    /**
     * Configure as under maintenance.
     */
    public function maintenance(): static
    {
        return $this->state(fn () => [
            'status' => ResourceStatus::Maintenance,
        ]);
    }

    /**
     * Configure as requiring approval for reservations.
     */
    public function requiresApproval(): static
    {
        return $this->state(fn () => [
            'requires_approval' => true,
        ]);
    }

    /**
     * Configure with a specific creator.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn () => [
            'created_by' => $user->id,
        ]);
    }
}
