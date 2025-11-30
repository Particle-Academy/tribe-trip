<?php

/**
 * Tests for member reservation management.
 *
 * Covers reservation creation, listing, and cancellation.
 */

use App\Enums\ReservationStatus;
use App\Livewire\Member\MyReservations;
use App\Livewire\Member\ResourceDetail;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\User;
use Livewire\Livewire;

/*
|--------------------------------------------------------------------------
| Access Control Tests
|--------------------------------------------------------------------------
*/

test('guests cannot access reservations page', function () {
    $this->get(route('member.reservations'))->assertRedirect(route('login'));
});

test('pending users cannot access reservations page', function () {
    $user = User::factory()->pending()->create();

    // Pending users are redirected to the pending approval page
    $this->actingAs($user)
        ->get(route('member.reservations'))
        ->assertRedirect(route('register.pending'));
});

test('approved members can access reservations page', function () {
    $member = createMember();

    $this->actingAs($member)
        ->get(route('member.reservations'))
        ->assertStatus(200);
});

/*
|--------------------------------------------------------------------------
| Reservation Listing Tests
|--------------------------------------------------------------------------
*/

test('member can see their reservations', function () {
    $member = createMember();
    $resource = Resource::factory()->create(['name' => 'Test Van']);

    Reservation::factory()->forUser($member)->forResource($resource)->tomorrow()->create();

    Livewire::actingAs($member)
        ->test(MyReservations::class)
        ->assertSee('Test Van');
});

test('member cannot see other users reservations', function () {
    $member = createMember();
    $otherMember = createMember();
    $resource = Resource::factory()->create(['name' => 'Other Van']);

    Reservation::factory()->forUser($otherMember)->forResource($resource)->create();

    Livewire::actingAs($member)
        ->test(MyReservations::class)
        ->assertDontSee('Other Van');
});

test('reservations are sorted by date', function () {
    $member = createMember();
    $resource = Resource::factory()->create();

    $laterReservation = Reservation::factory()
        ->forUser($member)
        ->forResource($resource)
        ->forTimeSlot(now()->addDays(5), now()->addDays(5)->addHours(2))
        ->confirmed()
        ->create();

    $soonerReservation = Reservation::factory()
        ->forUser($member)
        ->forResource($resource)
        ->forTimeSlot(now()->addDay(), now()->addDay()->addHours(2))
        ->confirmed()
        ->create();

    // Format dates as they appear in the component (full format: "Friday, December 5, 2025")
    $soonerFormatted = $soonerReservation->starts_at->format('l, F j, Y');
    $laterFormatted = $laterReservation->starts_at->format('l, F j, Y');

    Livewire::actingAs($member)
        ->test(MyReservations::class)
        ->assertSeeInOrder([$soonerFormatted, $laterFormatted]);
});

test('member can filter reservations by status', function () {
    $member = createMember();
    $resource = Resource::factory()->create();

    Reservation::factory()->forUser($member)->forResource($resource)->confirmed()->tomorrow()->create();
    Reservation::factory()->forUser($member)->forResource($resource)->completed()->create();

    Livewire::actingAs($member)
        ->test(MyReservations::class)
        ->set('filter', 'upcoming')
        ->assertSee('Confirmed');
});

/*
|--------------------------------------------------------------------------
| Reservation Creation Tests
|--------------------------------------------------------------------------
*/

test('member can create reservation from resource detail', function () {
    $member = createMember();
    $resource = Resource::factory()->create();

    $bookingDate = now()->addDay()->format('Y-m-d');
    $startTime = '10:00';
    $endTime = '14:00';

    Livewire::actingAs($member)
        ->test(ResourceDetail::class, ['resource' => $resource])
        ->set('bookingDate', $bookingDate)
        ->set('startTime', $startTime)
        ->set('endTime', $endTime)
        ->call('submitReservation')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('reservations', [
        'resource_id' => $resource->id,
        'user_id' => $member->id,
    ]);
});

test('cannot reserve resource for overlapping time slot', function () {
    $member = createMember();
    $resource = Resource::factory()->create();

    $bookingDate = now()->addDay()->format('Y-m-d');
    $startsAt = now()->addDay()->setTime(10, 0);
    $endsAt = now()->addDay()->setTime(14, 0);

    // Create existing reservation
    Reservation::factory()
        ->forResource($resource)
        ->forTimeSlot($startsAt, $endsAt)
        ->confirmed()
        ->create();

    Livewire::actingAs($member)
        ->test(ResourceDetail::class, ['resource' => $resource])
        ->set('bookingDate', $bookingDate)
        ->set('startTime', '10:00')
        ->set('endTime', '14:00')
        ->call('submitReservation')
        ->assertHasErrors();
});

test('cannot reserve resource under maintenance', function () {
    $member = createMember();
    $resource = Resource::factory()->maintenance()->create();

    Livewire::actingAs($member)
        ->test(ResourceDetail::class, ['resource' => $resource])
        ->set('bookingDate', now()->addDay()->format('Y-m-d'))
        ->set('startTime', '10:00')
        ->set('endTime', '12:00')
        ->call('submitReservation')
        ->assertHasErrors();
});

/*
|--------------------------------------------------------------------------
| Reservation Cancellation Tests
|--------------------------------------------------------------------------
*/

test('member can cancel their upcoming reservation', function () {
    $member = createMember();
    $resource = Resource::factory()->create();

    $reservation = Reservation::factory()
        ->forUser($member)
        ->forResource($resource)
        ->confirmed()
        ->tomorrow()
        ->create();

    // MyReservations uses a two-step flow: confirmCancel then cancelReservation
    Livewire::actingAs($member)
        ->test(MyReservations::class)
        ->call('confirmCancel', $reservation->id)
        ->assertSet('showCancelModal', true)
        ->call('cancelReservation')
        ->assertHasNoErrors();

    $reservation->refresh();
    expect($reservation->status)->toBe(ReservationStatus::Cancelled);
});

test('member cannot cancel past reservation', function () {
    $member = createMember();
    $resource = Resource::factory()->create();

    $reservation = Reservation::factory()
        ->forUser($member)
        ->forResource($resource)
        ->completed()
        ->create();

    // Completed reservations can't be cancelled - confirmCancel should show error
    Livewire::actingAs($member)
        ->test(MyReservations::class)
        ->call('confirmCancel', $reservation->id)
        ->assertSet('showCancelModal', false); // Modal won't open for non-cancellable

    $reservation->refresh();
    // Status should remain completed
    expect($reservation->status)->toBe(ReservationStatus::Completed);
});

test('member cannot cancel another users reservation', function () {
    $member = createMember();
    $otherMember = createMember();
    $resource = Resource::factory()->create();

    $reservation = Reservation::factory()
        ->forUser($otherMember)
        ->forResource($resource)
        ->confirmed()
        ->tomorrow()
        ->create();

    // Should throw a 404 when trying to find another user's reservation
    Livewire::actingAs($member)
        ->test(MyReservations::class)
        ->call('confirmCancel', $reservation->id);

    $reservation->refresh();
    // Status should remain confirmed
    expect($reservation->status)->toBe(ReservationStatus::Confirmed);
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

