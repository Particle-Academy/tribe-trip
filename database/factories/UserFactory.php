<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Factory for creating User models with various statuses and roles.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'status' => UserStatus::Approved,
            'status_changed_at' => now(),
            'role' => UserRole::Member,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create a user with pending approval status.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::Pending,
            'status_changed_at' => null,
            'status_reason' => null,
        ]);
    }

    /**
     * Create a user with approved status.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::Approved,
            'status_changed_at' => now(),
            'status_reason' => null,
        ]);
    }

    /**
     * Create a user with rejected status.
     */
    public function rejected(?string $reason = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::Rejected,
            'status_changed_at' => now(),
            'status_reason' => $reason ?? 'Registration requirements not met.',
        ]);
    }

    /**
     * Create an admin user.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Admin,
            'status' => UserStatus::Approved,
            'status_changed_at' => now(),
        ]);
    }

    /**
     * Create a member user.
     */
    public function member(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Member,
        ]);
    }

    /**
     * Create a suspended user.
     */
    public function suspended(?string $reason = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::Suspended,
            'status_changed_at' => now(),
            'status_reason' => $reason ?? 'Account suspended.',
        ]);
    }
}
