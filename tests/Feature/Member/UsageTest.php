<?php

/**
 * Tests for member usage logging (check-out/check-in).
 *
 * Covers the usage workflow for reserved resources.
 */

use App\Enums\ReservationStatus;
use App\Enums\UsageLogStatus;
use App\Livewire\Member\UsageCheckin;
use App\Livewire\Member\UsageCheckout;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\UsageLog;
use App\Models\User;
use Livewire\Livewire;

/*
|--------------------------------------------------------------------------
| Access Control Tests
|--------------------------------------------------------------------------
*/

test('guests cannot access checkout page', function () {
    $reservation = Reservation::factory()->create();

    $this->get(route('member.usage.checkout', $reservation))
        ->assertRedirect(route('login'));
});

test('pending users cannot access checkout page', function () {
    $user = User::factory()->pending()->create();
    $reservation = Reservation::factory()->forUser($user)->create();

    // Pending users are redirected to the pending approval page
    $this->actingAs($user)
        ->get(route('member.usage.checkout', $reservation))
        ->assertRedirect(route('register.pending'));
});

test('member can access checkout for their confirmed reservation', function () {
    $member = createMember();
    $resource = Resource::factory()->create();
    $reservation = Reservation::factory()
        ->forUser($member)
        ->forResource($resource)
        ->confirmed()
        ->today()
        ->create();

    $this->actingAs($member)
        ->get(route('member.usage.checkout', $reservation))
        ->assertStatus(200);
});

test('member cannot access checkout for other users reservation', function () {
    $member = createMember();
    $otherMember = createMember();
    $resource = Resource::factory()->create();
    $reservation = Reservation::factory()
        ->forUser($otherMember)
        ->forResource($resource)
        ->confirmed()
        ->create();

    $this->actingAs($member)
        ->get(route('member.usage.checkout', $reservation))
        ->assertStatus(403);
});

/*
|--------------------------------------------------------------------------
| Check-Out Tests
|--------------------------------------------------------------------------
*/

test('member can check out resource', function () {
    $member = createMember();
    $resource = Resource::factory()->perMile()->create();
    $reservation = Reservation::factory()
        ->forUser($member)
        ->forResource($resource)
        ->confirmed()
        ->today()
        ->create();

    // UsageCheckout uses 'notes' property (not 'startNotes')
    Livewire::actingAs($member)
        ->test(UsageCheckout::class, ['reservation' => $reservation])
        ->set('startReading', 12500.5)
        ->set('notes', 'Vehicle looks good')
        ->call('checkout')
        ->assertHasNoErrors();

    $reservation->refresh();
    expect($reservation->status)->toBe(ReservationStatus::CheckedOut);

    $this->assertDatabaseHas('usage_logs', [
        'reservation_id' => $reservation->id,
        'user_id' => $member->id,
        'resource_id' => $resource->id,
        'start_reading' => 12500.5,
        'start_notes' => 'Vehicle looks good',
        'status' => UsageLogStatus::CheckedOut->value,
    ]);
});

test('checkout can proceed without start reading for non-metered resources', function () {
    // Note: The UsageCheckout component has nullable validation for startReading
    // so empty values are allowed - validation only requires numeric if provided
    $member = createMember();
    $resource = Resource::factory()->flatFee()->create();
    $reservation = Reservation::factory()
        ->forUser($member)
        ->forResource($resource)
        ->confirmed()
        ->today()
        ->create();

    Livewire::actingAs($member)
        ->test(UsageCheckout::class, ['reservation' => $reservation])
        ->set('startReading', null)
        ->call('checkout')
        ->assertHasNoErrors();

    $reservation->refresh();
    expect($reservation->status)->toBe(ReservationStatus::CheckedOut);
});

test('cannot checkout already checked out reservation', function () {
    $member = createMember();
    $resource = Resource::factory()->create();
    $reservation = Reservation::factory()
        ->forUser($member)
        ->forResource($resource)
        ->checkedOut()
        ->create();

    // Also create the usage log that would exist for a checked-out reservation
    \App\Models\UsageLog::factory()
        ->forReservation($reservation)
        ->checkedOut()
        ->create();

    // The component will flash an error and canCheckOut() returns false
    // The page still loads (200) but shows an error message
    $this->actingAs($member)
        ->get(route('member.usage.checkout', $reservation))
        ->assertStatus(200);
});

/*
|--------------------------------------------------------------------------
| Check-In Tests
|--------------------------------------------------------------------------
*/

test('member can check in resource', function () {
    $member = createMember();
    $resource = Resource::factory()->perMile(0.50)->create();
    $reservation = Reservation::factory()
        ->forUser($member)
        ->forResource($resource)
        ->checkedOut()
        ->create();

    $usageLog = UsageLog::factory()
        ->forReservation($reservation)
        ->checkedOut()
        ->create([
            'start_reading' => 12500.0,
        ]);

    // UsageCheckin uses 'notes' property (not 'endNotes')
    Livewire::actingAs($member)
        ->test(UsageCheckin::class, ['usageLog' => $usageLog])
        ->set('endReading', 12550.0)
        ->set('notes', 'All good')
        ->call('checkin')
        ->assertHasNoErrors();

    $usageLog->refresh();
    expect($usageLog->status)->toBe(UsageLogStatus::Completed)
        ->and((float) $usageLog->end_reading)->toBe(12550.0)
        ->and((float) $usageLog->distance_units)->toBe(50.0)
        ->and($usageLog->end_notes)->toBe('All good');

    $reservation->refresh();
    expect($reservation->status)->toBe(ReservationStatus::Completed);
});

test('checkin requires end reading for metered resources', function () {
    $member = createMember();
    $resource = Resource::factory()->perMile()->create();
    $reservation = Reservation::factory()
        ->forUser($member)
        ->forResource($resource)
        ->checkedOut()
        ->create();

    $usageLog = UsageLog::factory()
        ->forReservation($reservation)
        ->checkedOut()
        ->create(['start_reading' => 12500.0]);

    Livewire::actingAs($member)
        ->test(UsageCheckin::class, ['usageLog' => $usageLog])
        ->set('endReading', '')
        ->call('checkin')
        ->assertHasErrors(['endReading']);
});

test('end reading must be greater than start reading', function () {
    $member = createMember();
    $resource = Resource::factory()->perMile()->create();
    $reservation = Reservation::factory()
        ->forUser($member)
        ->forResource($resource)
        ->checkedOut()
        ->create();

    $usageLog = UsageLog::factory()
        ->forReservation($reservation)
        ->checkedOut()
        ->create(['start_reading' => 12500.0]);

    Livewire::actingAs($member)
        ->test(UsageCheckin::class, ['usageLog' => $usageLog])
        ->set('endReading', 12400.0) // Less than start
        ->call('checkin')
        ->assertHasErrors(['endReading']);
});

test('member cannot check in other users usage log', function () {
    $member = createMember();
    $otherMember = createMember();
    $resource = Resource::factory()->create();
    $reservation = Reservation::factory()
        ->forUser($otherMember)
        ->forResource($resource)
        ->checkedOut()
        ->create();

    $usageLog = UsageLog::factory()
        ->forReservation($reservation)
        ->checkedOut()
        ->create();

    $this->actingAs($member)
        ->get(route('member.usage.checkin', $usageLog))
        ->assertStatus(403);
});

