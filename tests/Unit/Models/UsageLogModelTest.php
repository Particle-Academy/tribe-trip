<?php

/**
 * Unit tests for the UsageLog model.
 *
 * Tests relationships, status methods, calculations, and scopes.
 */

use App\Enums\UsageLogStatus;
use App\Models\InvoiceItem;
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

test('usage log belongs to reservation', function () {
    $user = User::factory()->approved()->create();
    $resource = Resource::factory()->create();
    $reservation = Reservation::factory()->forUser($user)->forResource($resource)->create();
    $usageLog = UsageLog::factory()->forReservation($reservation)->create();

    expect($usageLog->reservation->id)->toBe($reservation->id);
});

test('usage log belongs to user', function () {
    $user = User::factory()->approved()->create();
    $resource = Resource::factory()->create();
    $reservation = Reservation::factory()->forUser($user)->forResource($resource)->create();
    $usageLog = UsageLog::factory()->forReservation($reservation)->create();

    expect($usageLog->user->id)->toBe($user->id);
});

test('usage log belongs to resource', function () {
    $user = User::factory()->approved()->create();
    $resource = Resource::factory()->create();
    $reservation = Reservation::factory()->forUser($user)->forResource($resource)->create();
    $usageLog = UsageLog::factory()->forReservation($reservation)->create();

    expect($usageLog->resource->id)->toBe($resource->id);
});

test('usage log has one invoice item', function () {
    $user = User::factory()->approved()->create();
    $resource = Resource::factory()->create();
    $reservation = Reservation::factory()->forUser($user)->forResource($resource)->create();
    $usageLog = UsageLog::factory()->forReservation($reservation)->create();
    InvoiceItem::factory()->create(['usage_log_id' => $usageLog->id]);

    expect($usageLog->invoiceItem)->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Status Method Tests
|--------------------------------------------------------------------------
*/

test('isInProgress returns true for checked out logs', function () {
    $usageLog = UsageLog::factory()->checkedOut()->create();

    expect($usageLog->isInProgress())->toBeTrue();
});

test('isCompleted returns true for completed logs', function () {
    $usageLog = UsageLog::factory()->completed()->create();

    expect($usageLog->isCompleted())->toBeTrue();
});

test('isVerified returns true for verified logs', function () {
    $usageLog = UsageLog::factory()->verified()->create();

    expect($usageLog->isVerified())->toBeTrue();
});

test('isDisputed returns true for disputed logs', function () {
    $usageLog = UsageLog::factory()->disputed()->create();

    expect($usageLog->isDisputed())->toBeTrue();
});

test('canCheckIn returns true for in-progress logs', function () {
    $usageLog = UsageLog::factory()->checkedOut()->create();

    expect($usageLog->canCheckIn())->toBeTrue();
});

test('canCheckIn returns false for completed logs', function () {
    $usageLog = UsageLog::factory()->completed()->create();

    expect($usageLog->canCheckIn())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Check-In Method Tests
|--------------------------------------------------------------------------
*/

test('checkIn completes usage log with readings', function () {
    $usageLog = UsageLog::factory()->checkedOut()->create([
        'start_reading' => 12500.0,
        'checked_out_at' => now()->subHours(2),
    ]);

    $usageLog->checkIn(
        checkedInAt: now(),
        endReading: 12550.0,
        notes: 'All good'
    );

    $usageLog->refresh();
    expect($usageLog->status)->toBe(UsageLogStatus::Completed)
        ->and((float) $usageLog->end_reading)->toBe(12550.0)
        ->and((float) $usageLog->distance_units)->toBe(50.0)
        ->and($usageLog->end_notes)->toBe('All good')
        ->and($usageLog->checked_in_at)->not->toBeNull();
});

test('checkIn calculates duration', function () {
    $usageLog = UsageLog::factory()->checkedOut()->create([
        'checked_out_at' => now()->subHours(3),
    ]);

    $usageLog->checkIn(checkedInAt: now());

    $usageLog->refresh();
    expect($usageLog->duration_hours)->toBeGreaterThanOrEqual(2.9)
        ->and($usageLog->duration_hours)->toBeLessThanOrEqual(3.1);
});

/*
|--------------------------------------------------------------------------
| Verification Method Tests
|--------------------------------------------------------------------------
*/

test('verify changes status to verified', function () {
    $admin = User::factory()->admin()->create();
    $usageLog = UsageLog::factory()->completed()->create();

    $usageLog->verify($admin->id, 'Looks correct');

    $usageLog->refresh();
    expect($usageLog->status)->toBe(UsageLogStatus::Verified)
        ->and($usageLog->verified_by)->toBe($admin->id)
        ->and($usageLog->verified_at)->not->toBeNull()
        ->and($usageLog->admin_notes)->toBe('Looks correct');
});

test('dispute changes status to disputed', function () {
    $usageLog = UsageLog::factory()->completed()->create();

    $usageLog->dispute('Reading seems incorrect');

    $usageLog->refresh();
    expect($usageLog->status)->toBe(UsageLogStatus::Disputed)
        ->and($usageLog->admin_notes)->toBe('Reading seems incorrect');
});

test('cannot verify in-progress usage log', function () {
    $usageLog = UsageLog::factory()->checkedOut()->create();

    $result = $usageLog->verify();

    expect($result)->toBeFalse()
        ->and($usageLog->fresh()->status)->toBe(UsageLogStatus::CheckedOut);
});

/*
|--------------------------------------------------------------------------
| Invoiced Status Tests
|--------------------------------------------------------------------------
*/

test('isInvoiced returns true when invoice item exists', function () {
    $usageLog = UsageLog::factory()->completed()->create();
    InvoiceItem::factory()->create(['usage_log_id' => $usageLog->id]);

    expect($usageLog->isInvoiced())->toBeTrue();
});

test('isInvoiced returns false when no invoice item', function () {
    $usageLog = UsageLog::factory()->completed()->create();

    expect($usageLog->isInvoiced())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Formatted Attribute Tests
|--------------------------------------------------------------------------
*/

test('formattedDuration formats hours and minutes', function () {
    $usageLog = UsageLog::factory()->create(['duration_hours' => 2.5]);

    expect($usageLog->formatted_duration)->toBe('2h 30m');
});

test('formattedDuration formats hours only', function () {
    $usageLog = UsageLog::factory()->create(['duration_hours' => 3.0]);

    expect($usageLog->formatted_duration)->toBe('3h');
});

test('formattedCost formats correctly', function () {
    $usageLog = UsageLog::factory()->create(['calculated_cost' => 45.50]);

    expect($usageLog->formatted_cost)->toBe('$45.50');
});

/*
|--------------------------------------------------------------------------
| Query Scope Tests
|--------------------------------------------------------------------------
*/

test('inProgress scope returns checked out logs', function () {
    UsageLog::factory()->checkedOut()->count(2)->create();
    UsageLog::factory()->completed()->create();

    expect(UsageLog::inProgress()->count())->toBe(2);
});

test('completed scope returns completed logs', function () {
    UsageLog::factory()->completed()->count(3)->create();
    UsageLog::factory()->checkedOut()->create();

    expect(UsageLog::completed()->count())->toBe(3);
});

test('verified scope returns verified logs', function () {
    UsageLog::factory()->verified()->count(2)->create();
    UsageLog::factory()->completed()->create();

    expect(UsageLog::verified()->count())->toBe(2);
});

test('billable scope returns completed and verified logs', function () {
    UsageLog::factory()->completed()->count(2)->create();
    UsageLog::factory()->verified()->create();
    UsageLog::factory()->checkedOut()->create();

    expect(UsageLog::billable()->count())->toBe(3);
});

test('forUser scope filters by user', function () {
    $user1 = User::factory()->approved()->create();
    $user2 = User::factory()->approved()->create();
    $resource = Resource::factory()->create();

    $reservation1 = Reservation::factory()->forUser($user1)->forResource($resource)->create();
    $reservation2 = Reservation::factory()->forUser($user2)->forResource($resource)->create();

    UsageLog::factory()->forReservation($reservation1)->count(2)->create();
    UsageLog::factory()->forReservation($reservation2)->create();

    expect(UsageLog::forUser($user1->id)->count())->toBe(2);
});

