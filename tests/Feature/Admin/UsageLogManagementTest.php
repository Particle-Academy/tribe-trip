<?php

/**
 * Tests for admin usage log management.
 *
 * Covers usage log listing, verification, and dispute handling.
 */

use App\Enums\UsageLogStatus;
use App\Livewire\Admin\UsageLogList;
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

test('guests cannot access usage log management', function () {
    $this->get(route('admin.usage-logs'))->assertRedirect(route('login'));
});

test('non-admin users cannot access usage log management', function () {
    $user = createMember();

    $this->actingAs($user)->get(route('admin.usage-logs'))->assertStatus(403);
});

test('admin can access usage log management', function () {
    $admin = createAdmin();

    $this->actingAs($admin)->get(route('admin.usage-logs'))->assertStatus(200);
});

/*
|--------------------------------------------------------------------------
| Usage Log List Tests
|--------------------------------------------------------------------------
*/

test('usage log list shows all logs', function () {
    $admin = createAdmin();
    $member = createMember();
    $resource = Resource::factory()->create(['name' => 'Test Van']);
    $reservation = Reservation::factory()->forUser($member)->forResource($resource)->create();

    UsageLog::factory()->forReservation($reservation)->create();

    Livewire::actingAs($admin)
        ->test(UsageLogList::class)
        ->assertSee('Test Van')
        ->assertSee($member->name);
});

test('usage log list can filter by status', function () {
    $admin = createAdmin();
    $member = createMember();
    $resource = Resource::factory()->create();
    $reservation = Reservation::factory()->forUser($member)->forResource($resource)->create();

    UsageLog::factory()->forReservation($reservation)->completed()->create();
    $verifiedLog = UsageLog::factory()->forReservation($reservation)->verified()->create();

    Livewire::actingAs($admin)
        ->test(UsageLogList::class)
        ->set('status', UsageLogStatus::Verified->value)
        ->assertSee($verifiedLog->resource->name);
});

test('usage log list can filter by resource', function () {
    $admin = createAdmin();
    $member = createMember();

    $resource1 = Resource::factory()->create(['name' => 'Filter Test Van One']);
    $resource2 = Resource::factory()->create(['name' => 'Filter Test Van Two']);

    $reservation1 = Reservation::factory()->forUser($member)->forResource($resource1)->create();
    $reservation2 = Reservation::factory()->forUser($member)->forResource($resource2)->create();

    $log1 = UsageLog::factory()->forReservation($reservation1)->create();
    $log2 = UsageLog::factory()->forReservation($reservation2)->create();

    // Resource filter property is a string (from URL query)
    // Check that without filter both logs appear
    $component = Livewire::actingAs($admin)
        ->test(UsageLogList::class)
        ->assertSee($member->name);

    // After filtering, only logs for resource1 should appear in the table
    $component->set('resource', (string) $resource1->id);

    // Verify the query returns only the filtered log
    // Note: "Van Two" will still appear in the dropdown, so we check the log count
    $viewData = $component->viewData('logs');
    expect($viewData->count())->toBe(1)
        ->and($viewData->first()->resource_id)->toBe($resource1->id);
});

/*
|--------------------------------------------------------------------------
| Usage Log Verification Tests
|--------------------------------------------------------------------------
*/

test('admin can verify completed usage log', function () {
    $admin = createAdmin();
    $member = createMember();
    $resource = Resource::factory()->create();
    $reservation = Reservation::factory()->forUser($member)->forResource($resource)->create();

    $usageLog = UsageLog::factory()->forReservation($reservation)->completed()->create();

    Livewire::actingAs($admin)
        ->test(UsageLogList::class)
        ->call('openVerifyModal', $usageLog->id)
        ->call('verify');

    $usageLog->refresh();
    expect($usageLog->status)->toBe(UsageLogStatus::Verified)
        ->and($usageLog->verified_by)->toBe($admin->id)
        ->and($usageLog->verified_at)->not->toBeNull();
});

test('admin can dispute usage log', function () {
    $admin = createAdmin();
    $member = createMember();
    $resource = Resource::factory()->create();
    $reservation = Reservation::factory()->forUser($member)->forResource($resource)->create();

    $usageLog = UsageLog::factory()->forReservation($reservation)->completed()->create();

    Livewire::actingAs($admin)
        ->test(UsageLogList::class)
        ->call('openVerifyModal', $usageLog->id)
        ->set('adminNotes', 'Reading appears incorrect')
        ->call('dispute');

    $usageLog->refresh();
    expect($usageLog->status)->toBe(UsageLogStatus::Disputed)
        ->and($usageLog->admin_notes)->toBe('Reading appears incorrect');
});

test('cannot verify in-progress usage log', function () {
    $admin = createAdmin();
    $member = createMember();
    $resource = Resource::factory()->create();
    $reservation = Reservation::factory()->forUser($member)->forResource($resource)->create();

    $usageLog = UsageLog::factory()->forReservation($reservation)->checkedOut()->create();

    Livewire::actingAs($admin)
        ->test(UsageLogList::class)
        ->call('verify', $usageLog->id);

    $usageLog->refresh();
    // Should still be checked out
    expect($usageLog->status)->toBe(UsageLogStatus::CheckedOut);
});

/*
|--------------------------------------------------------------------------
| Usage Log Details Tests
|--------------------------------------------------------------------------
*/

test('usage log list shows calculated costs', function () {
    $admin = createAdmin();
    $member = createMember();
    $resource = Resource::factory()->perMile(0.50)->create();
    $reservation = Reservation::factory()->forUser($member)->forResource($resource)->create();

    UsageLog::factory()->forReservation($reservation)->create([
        'calculated_cost' => 45.50,
    ]);

    Livewire::actingAs($admin)
        ->test(UsageLogList::class)
        ->assertSee('$45.50');
});

test('usage log list shows duration', function () {
    $admin = createAdmin();
    $member = createMember();
    $resource = Resource::factory()->create();
    $reservation = Reservation::factory()->forUser($member)->forResource($resource)->create();

    UsageLog::factory()->forReservation($reservation)->create([
        'duration_hours' => 2.5,
    ]);

    Livewire::actingAs($admin)
        ->test(UsageLogList::class)
        ->assertSee('2h 30m');
});

