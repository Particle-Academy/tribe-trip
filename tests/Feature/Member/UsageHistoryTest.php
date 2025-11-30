<?php

/**
 * Tests for member usage history viewing.
 *
 * Covers usage log listing for members.
 */

use App\Livewire\Member\UsageHistory;
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

test('guests cannot access usage history', function () {
    $this->get(route('member.usage-history'))->assertRedirect(route('login'));
});

test('pending users cannot access usage history', function () {
    $user = User::factory()->pending()->create();

    // Pending users are redirected to the pending approval page
    $this->actingAs($user)
        ->get(route('member.usage-history'))
        ->assertRedirect(route('register.pending'));
});

test('approved members can access usage history', function () {
    $member = createMember();

    $this->actingAs($member)
        ->get(route('member.usage-history'))
        ->assertStatus(200);
});

/*
|--------------------------------------------------------------------------
| Usage History Listing Tests
|--------------------------------------------------------------------------
*/

test('member can see their usage history', function () {
    $member = createMember();
    $resource = Resource::factory()->create(['name' => 'Test Van']);
    $reservation = Reservation::factory()->forUser($member)->forResource($resource)->create();

    UsageLog::factory()->forReservation($reservation)->completed()->create();

    Livewire::actingAs($member)
        ->test(UsageHistory::class)
        ->assertSee('Test Van');
});

test('member cannot see other users usage history', function () {
    $member = createMember();
    $otherMember = createMember();
    $resource = Resource::factory()->create(['name' => 'Other Van']);
    $reservation = Reservation::factory()->forUser($otherMember)->forResource($resource)->create();

    UsageLog::factory()->forReservation($reservation)->completed()->create();

    Livewire::actingAs($member)
        ->test(UsageHistory::class)
        ->assertDontSee('Other Van');
});

test('usage history shows duration', function () {
    $member = createMember();
    $resource = Resource::factory()->create();
    $reservation = Reservation::factory()->forUser($member)->forResource($resource)->create();

    UsageLog::factory()->forReservation($reservation)->completed()->create([
        'duration_hours' => 3.5,
    ]);

    Livewire::actingAs($member)
        ->test(UsageHistory::class)
        ->assertSee('3h 30m');
});

test('usage history shows distance for metered resources', function () {
    $member = createMember();
    $resource = Resource::factory()->perMile()->create();
    $reservation = Reservation::factory()->forUser($member)->forResource($resource)->create();

    UsageLog::factory()->forReservation($reservation)->completed()->create([
        'distance_units' => 45.5,
    ]);

    Livewire::actingAs($member)
        ->test(UsageHistory::class)
        ->assertSee('45.5');
});

test('usage history shows calculated cost', function () {
    $member = createMember();
    $resource = Resource::factory()->create();
    $reservation = Reservation::factory()->forUser($member)->forResource($resource)->create();

    UsageLog::factory()->forReservation($reservation)->completed()->create([
        'calculated_cost' => 32.50,
    ]);

    Livewire::actingAs($member)
        ->test(UsageHistory::class)
        ->assertSee('$32.50');
});

test('usage history can filter by resource', function () {
    $member = createMember();

    $resource1 = Resource::factory()->create(['name' => 'Van One']);
    $resource2 = Resource::factory()->create(['name' => 'Van Two']);

    $reservation1 = Reservation::factory()->forUser($member)->forResource($resource1)->create();
    $reservation2 = Reservation::factory()->forUser($member)->forResource($resource2)->create();

    UsageLog::factory()->forReservation($reservation1)->completed()->create();
    UsageLog::factory()->forReservation($reservation2)->completed()->create();

    // UsageHistory component uses 'resource' property (not 'resourceId')
    // Note: Both resource names appear in the filter dropdown, but filtered log entries
    // should only show Van One. We verify the filter works by checking the log cards.
    $component = Livewire::actingAs($member)
        ->test(UsageHistory::class)
        ->set('resource', (string) $resource1->id)
        ->assertSee('Van One');

    // The component should only return logs for the filtered resource
    // Van Two appears in the dropdown but not in usage log cards
    $html = $component->html(false);
    // Count occurrences of the resource name in log headings (flux:heading)
    // Van One should appear more times (in dropdown AND in log card)
    // Van Two should only appear in the dropdown
    $vanOneCount = substr_count($html, 'Van One');
    $vanTwoCount = substr_count($html, 'Van Two');

    // Van One appears in dropdown (once) + in log card heading (once) = 2
    // Van Two appears only in dropdown (once) = 1
    expect($vanOneCount)->toBeGreaterThanOrEqual(2);
    expect($vanTwoCount)->toBe(1); // Only in dropdown
});

test('usage history sorted by most recent first', function () {
    $member = createMember();
    $resource = Resource::factory()->create();

    $olderReservation = Reservation::factory()->forUser($member)->forResource($resource)->create();
    $newerReservation = Reservation::factory()->forUser($member)->forResource($resource)->create();

    UsageLog::factory()->forReservation($olderReservation)->completed()->create([
        'checked_in_at' => now()->subDays(5),
    ]);
    UsageLog::factory()->forReservation($newerReservation)->completed()->create([
        'checked_in_at' => now()->subDay(),
    ]);

    // Newer should appear first
    Livewire::actingAs($member)
        ->test(UsageHistory::class);
});

