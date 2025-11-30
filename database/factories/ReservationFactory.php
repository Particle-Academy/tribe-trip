<?php

namespace Database\Factories;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reservation>
 */
class ReservationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('+1 day', '+2 weeks');
        $duration = fake()->randomElement([1, 2, 4, 8, 24]); // hours

        return [
            'resource_id' => Resource::factory(),
            'user_id' => User::factory(),
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->modify("+{$duration} hours"),
            'status' => ReservationStatus::Confirmed,
            'notes' => fake()->optional(0.3)->sentence(),
            'admin_notes' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'cancelled_by' => null,
            'confirmed_at' => now(),
            'confirmed_by' => null,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Status States
    |--------------------------------------------------------------------------
    */

    /**
     * Set as pending approval.
     */
    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => ReservationStatus::Pending,
            'confirmed_at' => null,
            'confirmed_by' => null,
        ]);
    }

    /**
     * Set as confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn () => [
            'status' => ReservationStatus::Confirmed,
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Set as checked out.
     */
    public function checkedOut(): static
    {
        return $this->state(fn () => [
            'status' => ReservationStatus::CheckedOut,
            'confirmed_at' => now()->subHour(),
        ]);
    }

    /**
     * Set as completed.
     */
    public function completed(): static
    {
        $startsAt = fake()->dateTimeBetween('-2 weeks', '-1 day');
        $duration = fake()->randomElement([1, 2, 4, 8]);

        return $this->state(fn () => [
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->modify("+{$duration} hours"),
            'status' => ReservationStatus::Completed,
            'confirmed_at' => (clone $startsAt)->modify('-1 day'),
        ]);
    }

    /**
     * Set as cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => ReservationStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_reason' => fake()->optional()->sentence(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Time States
    |--------------------------------------------------------------------------
    */

    /**
     * Set to start today.
     */
    public function today(): static
    {
        $startsAt = now()->setTime(fake()->numberBetween(8, 18), 0);

        return $this->state(fn () => [
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHours(fake()->numberBetween(1, 4)),
        ]);
    }

    /**
     * Set to start tomorrow.
     */
    public function tomorrow(): static
    {
        $startsAt = now()->addDay()->setTime(fake()->numberBetween(8, 18), 0);

        return $this->state(fn () => [
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHours(fake()->numberBetween(1, 4)),
        ]);
    }

    /**
     * Set to past date.
     */
    public function past(): static
    {
        $startsAt = fake()->dateTimeBetween('-1 month', '-1 day');
        $duration = fake()->randomElement([1, 2, 4, 8]);

        return $this->state(fn () => [
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->modify("+{$duration} hours"),
        ]);
    }

    /**
     * Set specific time range.
     */
    public function forTimeSlot($startsAt, $endsAt): static
    {
        return $this->state(fn () => [
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Relationship States
    |--------------------------------------------------------------------------
    */

    /**
     * Set for a specific resource.
     */
    public function forResource(Resource $resource): static
    {
        return $this->state(fn () => [
            'resource_id' => $resource->id,
        ]);
    }

    /**
     * Set for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn () => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Set cancelled by a specific user.
     */
    public function cancelledBy(User $user): static
    {
        return $this->cancelled()->state(fn () => [
            'cancelled_by' => $user->id,
        ]);
    }

    /**
     * Set confirmed by a specific admin.
     */
    public function confirmedBy(User $admin): static
    {
        return $this->confirmed()->state(fn () => [
            'confirmed_by' => $admin->id,
        ]);
    }
}
