<?php

namespace Database\Factories;

use App\Enums\InvitationStatus;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for creating Invitation models with various states.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invitation>
 */
class InvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'token' => Invitation::generateToken(),
            'email' => fake()->unique()->safeEmail(),
            'name' => fake()->name(),
            'status' => InvitationStatus::Pending,
            'expires_at' => now()->addDays(7),
            'invited_by' => User::factory()->admin(),
        ];
    }

    /**
     * Create an invitation with pending status.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvitationStatus::Pending,
            'expires_at' => now()->addDays(7),
        ]);
    }

    /**
     * Create an invitation that has been accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvitationStatus::Accepted,
            'accepted_by' => User::factory()->approved(),
            'accepted_at' => now(),
        ]);
    }

    /**
     * Create an invitation that has been revoked.
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvitationStatus::Revoked,
        ]);
    }

    /**
     * Create an invitation that has expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvitationStatus::Expired,
            'expires_at' => now()->subDay(),
        ]);
    }

    /**
     * Create an invitation expiring soon.
     */
    public function expiringSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvitationStatus::Pending,
            'expires_at' => now()->addHours(2),
        ]);
    }

    /**
     * Create an invitation without a name.
     */
    public function withoutName(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => null,
        ]);
    }
}
