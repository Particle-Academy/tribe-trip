<?php

namespace Database\Seeders;

use App\Enums\ReservationStatus;
use App\Enums\UsageLogStatus;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\UsageLog;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Seeder for creating sample data with users, resources, and usage history.
 *
 * Creates a realistic dataset for development and demo purposes including:
 * - 5 users (3 approved with activity, 2 pending approval)
 * - 5+ resources of various types
 * - Reservations and usage logs among approved users
 */
class SampleDataSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating sample data...');

        // Create users with different statuses
        $approvedUsers = $this->createApprovedUsers();
        $pendingUsers = $this->createPendingUsers();

        // Create resources
        $resources = $this->createResources($approvedUsers->first());

        // Create reservations and usage history for approved users
        $this->createUsageHistory($approvedUsers, $resources);

        $this->command->info('Sample data created successfully!');
        $this->command->table(
            ['Type', 'Count'],
            [
                ['Approved Users', $approvedUsers->count()],
                ['Pending Users', $pendingUsers->count()],
                ['Resources', $resources->count()],
            ]
        );
    }

    /**
     * Create approved users with realistic data.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function createApprovedUsers(): \Illuminate\Support\Collection
    {
        $users = collect();

        // Member 1 - Active community member with history
        $users->push(User::factory()->approved()->create([
            'name' => 'Sarah Johnson',
            'email' => 'sarah.johnson@example.com',
            'phone' => '555-123-4567',
        ]));

        // Member 2 - Regular user
        $users->push(User::factory()->approved()->create([
            'name' => 'Michael Chen',
            'email' => 'michael.chen@example.com',
            'phone' => '555-234-5678',
        ]));

        // Member 3 - Newer approved member
        $users->push(User::factory()->approved()->create([
            'name' => 'Emily Rodriguez',
            'email' => 'emily.rodriguez@example.com',
            'phone' => '555-345-6789',
            'status_changed_at' => now()->subDays(7), // Recently approved
        ]));

        $this->command->info('Created 3 approved users');

        return $users;
    }

    /**
     * Create pending users awaiting approval.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function createPendingUsers(): \Illuminate\Support\Collection
    {
        $users = collect();

        // Pending User 1 - Just registered
        $users->push(User::factory()->pending()->create([
            'name' => 'David Kim',
            'email' => 'david.kim@example.com',
            'phone' => '555-456-7890',
            'created_at' => now()->subHours(2),
        ]));

        // Pending User 2 - Registered a few days ago
        $users->push(User::factory()->pending()->create([
            'name' => 'Jessica Thompson',
            'email' => 'jessica.thompson@example.com',
            'phone' => '555-567-8901',
            'created_at' => now()->subDays(3),
        ]));

        $this->command->info('Created 2 pending users');

        return $users;
    }

    /**
     * Create resources of various types.
     *
     * @return \Illuminate\Support\Collection<int, Resource>
     */
    private function createResources(User $creator): \Illuminate\Support\Collection
    {
        $resources = collect();

        // Vehicle - Community Van (per day)
        $resources->push(Resource::factory()->vehicle()->perDay(45.00)->create([
            'name' => 'Community Van',
            'description' => 'Comfortable 12-passenger van perfect for group outings and community events. Features include AC, Bluetooth audio, and ample cargo space.',
            'max_reservation_days' => 7,
            'advance_booking_days' => 30,
            'created_by' => $creator->id,
        ]));

        // Vehicle - Pickup Truck (per mile)
        $resources->push(Resource::factory()->vehicle()->perMile(0.45)->create([
            'name' => 'F-150 Pickup Truck',
            'description' => 'Reliable Ford F-150 for hauling supplies, moving furniture, or project materials. Includes bed liner and tie-down straps.',
            'max_reservation_days' => 3,
            'advance_booking_days' => 14,
            'created_by' => $creator->id,
        ]));

        // Equipment - Pressure Washer (per hour)
        $resources->push(Resource::factory()->equipment()->perHour(15.00)->create([
            'name' => 'Pressure Washer',
            'description' => 'Professional-grade 3000 PSI pressure washer. Great for decks, driveways, and home exteriors. Training available upon request.',
            'max_reservation_days' => 1,
            'advance_booking_days' => 7,
            'created_by' => $creator->id,
        ]));

        // Equipment - Lawn Care Bundle (flat fee)
        $resources->push(Resource::factory()->equipment()->flatFee(25.00)->create([
            'name' => 'Lawn Care Bundle',
            'description' => 'Complete lawn care set including push mower, string trimmer, leaf blower, and garden tools. Perfect for weekend yard work.',
            'max_reservation_days' => 2,
            'advance_booking_days' => 7,
            'created_by' => $creator->id,
        ]));

        // Space - Community Room (per hour)
        $resources->push(Resource::factory()->space()->perHour(20.00)->create([
            'name' => 'Community Meeting Room',
            'description' => 'Spacious meeting room with seating for 30, projector, whiteboard, and kitchenette access. Ideal for clubs, classes, or gatherings.',
            'max_reservation_days' => 0, // Single day only
            'advance_booking_days' => 60,
            'requires_approval' => true,
            'created_by' => $creator->id,
        ]));

        // Equipment - Generator (per day)
        $resources->push(Resource::factory()->equipment()->perDay(35.00)->create([
            'name' => 'Portable Generator',
            'description' => '7500W portable generator for outdoor events, power outages, or work sites. Includes 50ft extension cord and fuel can.',
            'max_reservation_days' => 5,
            'advance_booking_days' => 14,
            'created_by' => $creator->id,
        ]));

        $this->command->info('Created 6 resources');

        return $resources;
    }

    /**
     * Create reservations and usage logs for approved users.
     */
    private function createUsageHistory(
        \Illuminate\Support\Collection $users,
        \Illuminate\Support\Collection $resources
    ): void {
        $completedCount = 0;
        $upcomingCount = 0;

        // Sarah Johnson - Active user with completed and upcoming reservations
        $sarah = $users[0];
        $van = $resources[0]; // Community Van
        $pressureWasher = $resources[2];

        // Completed reservation with verified usage (2 weeks ago)
        $this->createCompletedReservationWithUsage(
            $sarah,
            $van,
            now()->subDays(14)->setTime(9, 0),
            now()->subDays(13)->setTime(17, 0),
            35420.5, // Start odometer
            35562.3, // End odometer
            141.8    // Miles driven
        );
        $completedCount++;

        // Completed reservation (last week) with completed but unverified usage
        $this->createCompletedReservationWithUsage(
            $sarah,
            $pressureWasher,
            now()->subDays(7)->setTime(10, 0),
            now()->subDays(7)->setTime(14, 0),
            verified: false
        );
        $completedCount++;

        // Upcoming reservation
        Reservation::factory()
            ->forUser($sarah)
            ->forResource($van)
            ->confirmed()
            ->forTimeSlot(
                now()->addDays(5)->setTime(8, 0),
                now()->addDays(5)->setTime(18, 0)
            )
            ->create(['notes' => 'Family trip to the lake']);
        $upcomingCount++;

        // Michael Chen - Has completed reservations
        $michael = $users[1];
        $truck = $resources[1]; // Pickup Truck
        $lawnCare = $resources[3];

        // Completed reservation (3 weeks ago)
        $this->createCompletedReservationWithUsage(
            $michael,
            $truck,
            now()->subDays(21)->setTime(8, 0),
            now()->subDays(21)->setTime(16, 0),
            78234.2,
            78289.7,
            55.5
        );
        $completedCount++;

        // Completed reservation (10 days ago)
        $this->createCompletedReservationWithUsage(
            $michael,
            $lawnCare,
            now()->subDays(10)->setTime(9, 0),
            now()->subDays(10)->setTime(13, 0),
            verified: true
        );
        $completedCount++;

        // Pending reservation for Community Room (requires approval)
        Reservation::factory()
            ->forUser($michael)
            ->forResource($resources[4]) // Community Meeting Room
            ->pending()
            ->forTimeSlot(
                now()->addDays(10)->setTime(18, 0),
                now()->addDays(10)->setTime(21, 0)
            )
            ->create(['notes' => 'Book club monthly meeting']);
        $upcomingCount++;

        // Emily Rodriguez - Newer member with first usage
        $emily = $users[2];
        $generator = $resources[5];

        // Her first completed reservation (3 days ago)
        $this->createCompletedReservationWithUsage(
            $emily,
            $generator,
            now()->subDays(3)->setTime(8, 0),
            now()->subDays(2)->setTime(18, 0),
            verified: false
        );
        $completedCount++;

        // Upcoming confirmed reservation
        Reservation::factory()
            ->forUser($emily)
            ->forResource($pressureWasher)
            ->confirmed()
            ->forTimeSlot(
                now()->addDays(2)->setTime(10, 0),
                now()->addDays(2)->setTime(14, 0)
            )
            ->create(['notes' => 'Cleaning the deck before summer']);
        $upcomingCount++;

        $this->command->info("Created {$completedCount} completed reservations with usage logs");
        $this->command->info("Created {$upcomingCount} upcoming/pending reservations");
    }

    /**
     * Create a completed reservation with associated usage log.
     */
    private function createCompletedReservationWithUsage(
        User $user,
        Resource $resource,
        \Carbon\Carbon $startsAt,
        \Carbon\Carbon $endsAt,
        ?float $startReading = null,
        ?float $endReading = null,
        ?float $distanceUnits = null,
        bool $verified = true
    ): void {
        // Create the completed reservation
        $reservation = Reservation::factory()
            ->forUser($user)
            ->forResource($resource)
            ->completed()
            ->create([
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'confirmed_at' => $startsAt->copy()->subDay(),
            ]);

        // Calculate duration in hours
        $durationHours = $startsAt->diffInMinutes($endsAt) / 60;

        // Calculate cost based on resource pricing
        $calculatedCost = $resource->calculateReservationCost($startsAt, $endsAt);

        // Create the usage log
        $usageLogData = [
            'reservation_id' => $reservation->id,
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'status' => $verified ? UsageLogStatus::Verified : UsageLogStatus::Completed,
            'checked_out_at' => $startsAt,
            'checked_in_at' => $endsAt,
            'duration_hours' => $durationHours,
            'calculated_cost' => $calculatedCost,
            'start_reading' => $startReading,
            'end_reading' => $endReading,
            'distance_units' => $distanceUnits,
        ];

        if ($verified) {
            // Get the test admin for verification
            $admin = User::where('email', 'admin@test.local')->first();
            $usageLogData['verified_by'] = $admin?->id;
            $usageLogData['verified_at'] = $endsAt->copy()->addHours(2);
        }

        UsageLog::create($usageLogData);
    }
}
