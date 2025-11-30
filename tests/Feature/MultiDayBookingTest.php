<?php

/**
 * Tests for multi-day booking functionality.
 *
 * Tests the max_reservation_days setting (0 = single day, N = max days, null = unlimited),
 * helper methods, validation, and cost calculations.
 */

use App\Enums\PricingModel;
use App\Enums\PricingUnit;
use App\Enums\ReservationStatus;
use App\Enums\ResourceStatus;
use App\Livewire\Admin\ResourceForm;
use App\Livewire\Member\ResourceDetail;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Resource Model Helper Method Tests
|--------------------------------------------------------------------------
*/

test('allowsMultiDayBooking returns true when max_reservation_days is null', function () {
    $resource = Resource::factory()->create(['max_reservation_days' => null]);

    expect($resource->allowsMultiDayBooking())->toBeTrue();
});

test('allowsMultiDayBooking returns true when max_reservation_days is greater than 0', function () {
    $resource = Resource::factory()->create(['max_reservation_days' => 5]);

    expect($resource->allowsMultiDayBooking())->toBeTrue();
});

test('allowsMultiDayBooking returns false when max_reservation_days is 0', function () {
    $resource = Resource::factory()->create(['max_reservation_days' => 0]);

    expect($resource->allowsMultiDayBooking())->toBeFalse();
});

test('maxBookingDays returns the max_reservation_days value', function () {
    $nullResource = Resource::factory()->create(['max_reservation_days' => null]);
    $zeroResource = Resource::factory()->create(['max_reservation_days' => 0]);
    $limitedResource = Resource::factory()->create(['max_reservation_days' => 7]);

    expect($nullResource->maxBookingDays())->toBeNull()
        ->and($zeroResource->maxBookingDays())->toBe(0)
        ->and($limitedResource->maxBookingDays())->toBe(7);
});

test('isValidBookingDuration returns true for single day when max_reservation_days is 0', function () {
    $resource = Resource::factory()->create(['max_reservation_days' => 0]);

    expect($resource->isValidBookingDuration(1))->toBeTrue()
        ->and($resource->isValidBookingDuration(2))->toBeFalse();
});

test('isValidBookingDuration returns true for any duration when max_reservation_days is null', function () {
    $resource = Resource::factory()->create(['max_reservation_days' => null]);

    expect($resource->isValidBookingDuration(1))->toBeTrue()
        ->and($resource->isValidBookingDuration(100))->toBeTrue();
});

test('isValidBookingDuration respects max_reservation_days limit', function () {
    $resource = Resource::factory()->create(['max_reservation_days' => 5]);

    expect($resource->isValidBookingDuration(3))->toBeTrue()
        ->and($resource->isValidBookingDuration(5))->toBeTrue()
        ->and($resource->isValidBookingDuration(6))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Cost Calculation Tests for Multi-Day
|--------------------------------------------------------------------------
*/

test('calculateReservationCost returns flat fee for flat fee pricing regardless of duration', function () {
    $resource = Resource::factory()->flatFee(50.00)->create();
    $startsAt = Carbon::tomorrow()->setTime(9, 0);
    $endsAt = Carbon::tomorrow()->addDays(3)->setTime(17, 0);

    expect($resource->calculateReservationCost($startsAt, $endsAt))->toBe(50.00);
});

test('calculateReservationCost calculates hours for per-hour pricing', function () {
    $resource = Resource::factory()->perHour(10.00)->create();
    $startsAt = Carbon::tomorrow()->setTime(9, 0);
    $endsAt = Carbon::tomorrow()->setTime(14, 0); // 5 hours

    expect($resource->calculateReservationCost($startsAt, $endsAt))->toBe(50.00);
});

test('calculateReservationCost calculates days for per-day pricing', function () {
    $resource = Resource::factory()->perDay(25.00)->create();
    $startsAt = Carbon::tomorrow()->setTime(9, 0);
    $endsAt = Carbon::tomorrow()->addDays(2)->setTime(17, 0); // 3 days

    expect($resource->calculateReservationCost($startsAt, $endsAt))->toBe(75.00);
});

/*
|--------------------------------------------------------------------------
| Admin Resource Form Tests
|--------------------------------------------------------------------------
*/

test('admin can set max_reservation_days to 0 for single day only', function () {
    $admin = User::factory()->admin()->approved()->create();

    Livewire::actingAs($admin)
        ->test(ResourceForm::class)
        ->set('name', 'Test Resource')
        ->set('type', 'vehicle')
        ->set('status', 'active')
        ->set('pricing_model', 'flat_fee')
        ->set('rate', '25.00')
        ->set('max_reservation_days', 0)
        ->call('save')
        ->assertHasNoErrors();

    $resource = Resource::where('name', 'Test Resource')->first();
    expect($resource->max_reservation_days)->toBe(0)
        ->and($resource->allowsMultiDayBooking())->toBeFalse();
});

test('admin can set max_reservation_days to positive number', function () {
    $admin = User::factory()->admin()->approved()->create();

    Livewire::actingAs($admin)
        ->test(ResourceForm::class)
        ->set('name', 'Multi-Day Resource')
        ->set('type', 'vehicle')
        ->set('status', 'active')
        ->set('pricing_model', 'flat_fee')
        ->set('rate', '50.00')
        ->set('max_reservation_days', 7)
        ->call('save')
        ->assertHasNoErrors();

    $resource = Resource::where('name', 'Multi-Day Resource')->first();
    expect($resource->max_reservation_days)->toBe(7)
        ->and($resource->allowsMultiDayBooking())->toBeTrue();
});

test('admin can leave max_reservation_days empty for unlimited', function () {
    $admin = User::factory()->admin()->approved()->create();

    Livewire::actingAs($admin)
        ->test(ResourceForm::class)
        ->set('name', 'Unlimited Resource')
        ->set('type', 'equipment')
        ->set('status', 'active')
        ->set('pricing_model', 'flat_fee')
        ->set('rate', '30.00')
        ->set('max_reservation_days', null)
        ->call('save')
        ->assertHasNoErrors();

    $resource = Resource::where('name', 'Unlimited Resource')->first();
    expect($resource->max_reservation_days)->toBeNull()
        ->and($resource->allowsMultiDayBooking())->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Member Booking Modal Tests
|--------------------------------------------------------------------------
*/

test('member can make single day booking on single-day-only resource', function () {
    $user = User::factory()->approved()->create();
    $resource = Resource::factory()->create([
        'max_reservation_days' => 0,
        'status' => ResourceStatus::Active,
        'requires_approval' => false,
    ]);

    Livewire::actingAs($user)
        ->test(ResourceDetail::class, ['resource' => $resource])
        ->call('openBookingModal', Carbon::tomorrow()->format('Y-m-d'))
        ->set('bookingDate', Carbon::tomorrow()->format('Y-m-d'))
        ->set('endDate', Carbon::tomorrow()->format('Y-m-d'))
        ->set('startTime', '09:00')
        ->set('endTime', '17:00')
        ->call('submitReservation')
        ->assertHasNoErrors();

    expect(Reservation::count())->toBe(1);
});

test('member cannot make multi-day booking on single-day-only resource', function () {
    $user = User::factory()->approved()->create();
    $resource = Resource::factory()->create([
        'max_reservation_days' => 0,
        'status' => ResourceStatus::Active,
        'requires_approval' => false,
    ]);

    // When resource doesn't allow multi-day, the endDate input is ignored
    // and the booking will use bookingDate for both start and end
    Livewire::actingAs($user)
        ->test(ResourceDetail::class, ['resource' => $resource])
        ->call('openBookingModal', Carbon::tomorrow()->format('Y-m-d'))
        ->set('bookingDate', Carbon::tomorrow()->format('Y-m-d'))
        ->set('endDate', Carbon::tomorrow()->addDays(2)->format('Y-m-d')) // This gets ignored
        ->set('startTime', '09:00')
        ->set('endTime', '17:00')
        ->call('submitReservation')
        ->assertHasNoErrors();

    // Verify the reservation was created as single-day only
    expect(Reservation::count())->toBe(1);
    $reservation = Reservation::first();
    // Both start and end should be on the same day (bookingDate)
    expect($reservation->starts_at->format('Y-m-d'))->toBe(Carbon::tomorrow()->format('Y-m-d'))
        ->and($reservation->ends_at->format('Y-m-d'))->toBe(Carbon::tomorrow()->format('Y-m-d'));
});

test('member can make multi-day booking within limit', function () {
    $user = User::factory()->approved()->create();
    $resource = Resource::factory()->create([
        'max_reservation_days' => 5,
        'status' => ResourceStatus::Active,
        'requires_approval' => false,
    ]);

    Livewire::actingAs($user)
        ->test(ResourceDetail::class, ['resource' => $resource])
        ->call('openBookingModal', Carbon::tomorrow()->format('Y-m-d'))
        ->set('bookingDate', Carbon::tomorrow()->format('Y-m-d'))
        ->set('endDate', Carbon::tomorrow()->addDays(3)->format('Y-m-d'))
        ->set('startTime', '09:00')
        ->set('endTime', '17:00')
        ->call('submitReservation')
        ->assertHasNoErrors();

    expect(Reservation::count())->toBe(1);
    $reservation = Reservation::first();
    expect($reservation->starts_at->format('Y-m-d'))->toBe(Carbon::tomorrow()->format('Y-m-d'))
        ->and($reservation->ends_at->format('Y-m-d'))->toBe(Carbon::tomorrow()->addDays(3)->format('Y-m-d'));
});

test('member cannot exceed max_reservation_days limit', function () {
    $user = User::factory()->approved()->create();
    $resource = Resource::factory()->create([
        'max_reservation_days' => 3,
        'status' => ResourceStatus::Active,
    ]);

    Livewire::actingAs($user)
        ->test(ResourceDetail::class, ['resource' => $resource])
        ->call('openBookingModal', Carbon::tomorrow()->format('Y-m-d'))
        ->set('bookingDate', Carbon::tomorrow()->format('Y-m-d'))
        ->set('endDate', Carbon::tomorrow()->addDays(5)->format('Y-m-d'))
        ->set('startTime', '09:00')
        ->set('endTime', '17:00')
        ->call('submitReservation')
        ->assertHasErrors(['endDate']);

    expect(Reservation::count())->toBe(0);
});

test('member can make unlimited duration booking when max_reservation_days is null', function () {
    $user = User::factory()->approved()->create();
    $resource = Resource::factory()->create([
        'max_reservation_days' => null,
        'status' => ResourceStatus::Active,
        'requires_approval' => false,
    ]);

    Livewire::actingAs($user)
        ->test(ResourceDetail::class, ['resource' => $resource])
        ->call('openBookingModal', Carbon::tomorrow()->format('Y-m-d'))
        ->set('bookingDate', Carbon::tomorrow()->format('Y-m-d'))
        ->set('endDate', Carbon::tomorrow()->addDays(14)->format('Y-m-d'))
        ->set('startTime', '09:00')
        ->set('endTime', '17:00')
        ->call('submitReservation')
        ->assertHasNoErrors();

    expect(Reservation::count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Calendar Display Tests
|--------------------------------------------------------------------------
*/

test('calendar marks multi-day reservations correctly', function () {
    $user = User::factory()->approved()->create();
    $resource = Resource::factory()->create([
        'max_reservation_days' => null,
        'status' => ResourceStatus::Active,
    ]);

    // Create a multi-day reservation first, before mounting the component
    $startOfMonth = now()->startOfMonth();
    $reservationStart = $startOfMonth->copy()->addDays(10)->setTime(9, 0);
    $reservationEnd = $startOfMonth->copy()->addDays(13)->setTime(17, 0);

    Reservation::factory()->create([
        'resource_id' => $resource->id,
        'user_id' => $user->id,
        'starts_at' => $reservationStart,
        'ends_at' => $reservationEnd,
        'status' => ReservationStatus::Confirmed,
    ]);

    // Now mount the component - the calendar will include the reservation
    $component = Livewire::actingAs($user)
        ->test(ResourceDetail::class, ['resource' => $resource]);

    // The calendar weeks should include multi-day information
    $calendarWeeks = $component->get('calendarWeeks');

    // Find the days that should be marked as multi-day
    $hasMultiDayDay = collect($calendarWeeks)->flatten(1)->first(fn ($day) => $day['hasMultiDay'] ?? false);

    expect($hasMultiDayDay)->not->toBeNull();
});
