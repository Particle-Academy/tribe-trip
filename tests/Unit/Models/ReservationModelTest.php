<?php

/**
 * Unit tests for the Reservation model.
 *
 * Tests relationships, status methods, time calculations, and scopes.
 */

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\UsageLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Relationship Tests
|--------------------------------------------------------------------------
*/

test('reservation belongs to resource', function () {
    $resource = Resource::factory()->create();
    $user = User::factory()->approved()->create();
    $reservation = Reservation::factory()->forResource($resource)->forUser($user)->create();

    expect($reservation->resource->id)->toBe($resource->id);
});

test('reservation belongs to user', function () {
    $user = User::factory()->approved()->create();
    $resource = Resource::factory()->create();
    $reservation = Reservation::factory()->forUser($user)->forResource($resource)->create();

    expect($reservation->user->id)->toBe($user->id);
});

test('reservation has one usage log', function () {
    $user = User::factory()->approved()->create();
    $resource = Resource::factory()->create();
    $reservation = Reservation::factory()->forUser($user)->forResource($resource)->create();
    UsageLog::factory()->forReservation($reservation)->create();

    expect($reservation->usageLog)->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Status Method Tests
|--------------------------------------------------------------------------
*/

test('isPending returns true for pending reservations', function () {
    $reservation = Reservation::factory()->pending()->create();

    expect($reservation->isPending())->toBeTrue();
});

test('isConfirmed returns true for confirmed reservations', function () {
    $reservation = Reservation::factory()->confirmed()->create();

    expect($reservation->isConfirmed())->toBeTrue();
});

test('isCheckedOut returns true for checked out reservations', function () {
    $reservation = Reservation::factory()->checkedOut()->create();

    expect($reservation->isCheckedOut())->toBeTrue();
});

test('isCompleted returns true for completed reservations', function () {
    $reservation = Reservation::factory()->completed()->create();

    expect($reservation->isCompleted())->toBeTrue();
});

test('isCancelled returns true for cancelled reservations', function () {
    $reservation = Reservation::factory()->cancelled()->create();

    expect($reservation->isCancelled())->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Status Change Method Tests
|--------------------------------------------------------------------------
*/

test('confirm changes status to confirmed', function () {
    $reservation = Reservation::factory()->pending()->create();

    $reservation->confirm();

    $reservation->refresh();
    expect($reservation->status)->toBe(ReservationStatus::Confirmed)
        ->and($reservation->confirmed_at)->not->toBeNull();
});

test('cancel changes status to cancelled', function () {
    $user = User::factory()->approved()->create();
    $resource = Resource::factory()->create();
    $reservation = Reservation::factory()
        ->forUser($user)
        ->forResource($resource)
        ->confirmed()
        ->tomorrow()
        ->create();

    $reservation->cancel('Changed my mind');

    $reservation->refresh();
    expect($reservation->status)->toBe(ReservationStatus::Cancelled)
        ->and($reservation->cancellation_reason)->toBe('Changed my mind')
        ->and($reservation->cancelled_at)->not->toBeNull();
});

test('checkOut changes status to checked out', function () {
    $reservation = Reservation::factory()->confirmed()->create();

    $reservation->checkOut();

    expect($reservation->fresh()->status)->toBe(ReservationStatus::CheckedOut);
});

test('complete changes status to completed', function () {
    $reservation = Reservation::factory()->checkedOut()->create();

    $reservation->complete();

    expect($reservation->fresh()->status)->toBe(ReservationStatus::Completed);
});

/*
|--------------------------------------------------------------------------
| Cancellation Rules Tests
|--------------------------------------------------------------------------
*/

test('canBeCancelled returns true for future confirmed reservations', function () {
    $user = User::factory()->approved()->create();
    $resource = Resource::factory()->create();
    $reservation = Reservation::factory()
        ->forUser($user)
        ->forResource($resource)
        ->confirmed()
        ->tomorrow()
        ->create();

    expect($reservation->canBeCancelled())->toBeTrue();
});

test('canBeCancelled returns false for past reservations', function () {
    $reservation = Reservation::factory()->completed()->create();

    expect($reservation->canBeCancelled())->toBeFalse();
});

test('canBeCancelled returns false for already cancelled reservations', function () {
    $reservation = Reservation::factory()->cancelled()->create();

    expect($reservation->canBeCancelled())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Time Attribute Tests
|--------------------------------------------------------------------------
*/

test('durationHours calculates correct duration', function () {
    $startsAt = now();
    $endsAt = now()->addHours(3);

    $reservation = Reservation::factory()
        ->forTimeSlot($startsAt, $endsAt)
        ->create();

    expect($reservation->duration_hours)->toBe(3.0);
});

test('isUpcoming returns true for future reservations', function () {
    $user = User::factory()->approved()->create();
    $resource = Resource::factory()->create();
    $reservation = Reservation::factory()
        ->forUser($user)
        ->forResource($resource)
        ->tomorrow()
        ->create();

    expect($reservation->is_upcoming)->toBeTrue();
});

test('isPast returns true for past reservations', function () {
    $reservation = Reservation::factory()->completed()->create();

    expect($reservation->is_past)->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Query Scope Tests
|--------------------------------------------------------------------------
*/

test('upcoming scope returns future reservations', function () {
    $user = User::factory()->approved()->create();
    $resource = Resource::factory()->create();

    Reservation::factory()->forUser($user)->forResource($resource)->tomorrow()->count(2)->create();
    Reservation::factory()->forUser($user)->forResource($resource)->completed()->create();

    expect(Reservation::upcoming()->count())->toBe(2);
});

test('forResource scope filters by resource', function () {
    $resource1 = Resource::factory()->create();
    $resource2 = Resource::factory()->create();
    $user = User::factory()->approved()->create();

    Reservation::factory()->forUser($user)->forResource($resource1)->count(2)->create();
    Reservation::factory()->forUser($user)->forResource($resource2)->create();

    expect(Reservation::forResource($resource1->id)->count())->toBe(2);
});

test('forUser scope filters by user', function () {
    $user1 = User::factory()->approved()->create();
    $user2 = User::factory()->approved()->create();
    $resource = Resource::factory()->create();

    Reservation::factory()->forUser($user1)->forResource($resource)->count(3)->create();
    Reservation::factory()->forUser($user2)->forResource($resource)->create();

    expect(Reservation::forUser($user1->id)->count())->toBe(3);
});

/*
|--------------------------------------------------------------------------
| Slot Availability Tests
|--------------------------------------------------------------------------
*/

test('isSlotAvailable returns true for open time slot', function () {
    $resource = Resource::factory()->create();

    $startsAt = now()->addDay()->setTime(10, 0);
    $endsAt = now()->addDay()->setTime(14, 0);

    expect(Reservation::isSlotAvailable($resource->id, $startsAt, $endsAt))->toBeTrue();
});

test('isSlotAvailable returns false for overlapping time slot', function () {
    $resource = Resource::factory()->create();
    $user = User::factory()->approved()->create();

    $startsAt = now()->addDay()->setTime(10, 0);
    $endsAt = now()->addDay()->setTime(14, 0);

    Reservation::factory()
        ->forResource($resource)
        ->forUser($user)
        ->forTimeSlot($startsAt, $endsAt)
        ->confirmed()
        ->create();

    // Try to reserve overlapping slot
    $newStartsAt = now()->addDay()->setTime(12, 0);
    $newEndsAt = now()->addDay()->setTime(16, 0);

    expect(Reservation::isSlotAvailable($resource->id, $newStartsAt, $newEndsAt))->toBeFalse();
});

