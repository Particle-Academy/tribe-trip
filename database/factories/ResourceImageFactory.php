<?php

namespace Database\Factories;

use App\Models\Resource;
use App\Models\ResourceImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ResourceImage>
 */
class ResourceImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = fake()->uuid() . '.jpg';

        return [
            'resource_id' => Resource::factory(),
            'path' => 'resource-images/' . $filename,
            'filename' => $filename,
            'order' => fake()->numberBetween(0, 10),
            'is_primary' => false,
        ];
    }

    /**
     * Mark as primary image.
     */
    public function primary(): static
    {
        return $this->state(fn () => [
            'is_primary' => true,
            'order' => 0,
        ]);
    }

    /**
     * Set specific order.
     */
    public function order(int $order): static
    {
        return $this->state(fn () => [
            'order' => $order,
        ]);
    }
}
