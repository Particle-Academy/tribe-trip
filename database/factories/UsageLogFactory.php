<?php

namespace Database\Factories;

use App\Enums\UsageLogStatus;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\UsageLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UsageLog>
 */
class UsageLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $checkedOutAt = fake()->dateTimeBetween('-1 week', 'now');
        $checkedInAt = (clone $checkedOutAt)->modify('+' . fake()->numberBetween(1, 8) . ' hours');
        $startReading = fake()->randomFloat(1, 1000, 50000);
        $endReading = $startReading + fake()->randomFloat(1, 5, 100);

        return [
            'reservation_id' => Reservation::factory(),
            'user_id' => User::factory(),
            'resource_id' => Resource::factory(),
            'status' => UsageLogStatus::Completed,
            'checked_out_at' => $checkedOutAt,
            'start_reading' => $startReading,
            'start_photo_path' => null,
            'start_notes' => fake()->optional(0.3)->sentence(),
            'checked_in_at' => $checkedInAt,
            'end_reading' => $endReading,
            'end_photo_path' => null,
            'end_notes' => fake()->optional(0.3)->sentence(),
            'duration_hours' => round(($checkedInAt->getTimestamp() - $checkedOutAt->getTimestamp()) / 3600, 2),
            'distance_units' => round($endReading - $startReading, 2),
            'calculated_cost' => fake()->randomFloat(2, 10, 200),
            'verified_by' => null,
            'verified_at' => null,
            'admin_notes' => null,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Status States
    |--------------------------------------------------------------------------
    */

    /**
     * Set as checked out (in progress).
     */
    public function checkedOut(): static
    {
        return $this->state(fn () => [
            'status' => UsageLogStatus::CheckedOut,
            'checked_in_at' => null,
            'end_reading' => null,
            'end_photo_path' => null,
            'end_notes' => null,
            'duration_hours' => null,
            'distance_units' => null,
            'calculated_cost' => null,
        ]);
    }

    /**
     * Set as completed.
     */
    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => UsageLogStatus::Completed,
        ]);
    }

    /**
     * Set as verified.
     */
    public function verified(): static
    {
        return $this->state(fn () => [
            'status' => UsageLogStatus::Verified,
            'verified_by' => User::factory(),
            'verified_at' => now(),
        ]);
    }

    /**
     * Set as disputed.
     */
    public function disputed(): static
    {
        return $this->state(fn () => [
            'status' => UsageLogStatus::Disputed,
            'admin_notes' => fake()->sentence(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Photo States
    |--------------------------------------------------------------------------
    */

    /**
     * Include start photo.
     */
    public function withStartPhoto(): static
    {
        return $this->state(fn () => [
            'start_photo_path' => 'usage-photos/' . fake()->uuid() . '.jpg',
        ]);
    }

    /**
     * Include end photo.
     */
    public function withEndPhoto(): static
    {
        return $this->state(fn () => [
            'end_photo_path' => 'usage-photos/' . fake()->uuid() . '.jpg',
        ]);
    }

    /**
     * Include both photos.
     */
    public function withPhotos(): static
    {
        return $this->withStartPhoto()->withEndPhoto();
    }

    /*
    |--------------------------------------------------------------------------
    | Relationship States
    |--------------------------------------------------------------------------
    */

    /**
     * Set for a specific reservation.
     */
    public function forReservation(Reservation $reservation): static
    {
        return $this->state(fn () => [
            'reservation_id' => $reservation->id,
            'user_id' => $reservation->user_id,
            'resource_id' => $reservation->resource_id,
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
     * Set for a specific resource.
     */
    public function forResource(Resource $resource): static
    {
        return $this->state(fn () => [
            'resource_id' => $resource->id,
        ]);
    }

    /**
     * Set verified by a specific admin.
     */
    public function verifiedBy(User $admin): static
    {
        return $this->verified()->state(fn () => [
            'verified_by' => $admin->id,
        ]);
    }
}
