<?php

/**
 * Unit tests for the User model.
 *
 * Tests relationships, status methods, role methods, and scopes.
 */

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Invoice;
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

test('user has many reservations', function () {
    $user = User::factory()->approved()->create();
    $resource = Resource::factory()->create();

    Reservation::factory()->forUser($user)->forResource($resource)->count(3)->create();

    expect($user->reservations)->toHaveCount(3);
});

test('user has many invoices', function () {
    $user = User::factory()->approved()->create();

    Invoice::factory()->forUser($user)->count(2)->create();

    expect($user->invoices)->toHaveCount(2);
});

test('user has many usage logs', function () {
    $user = User::factory()->approved()->create();
    $resource = Resource::factory()->create();
    $reservation = Reservation::factory()->forUser($user)->forResource($resource)->create();

    UsageLog::factory()->forReservation($reservation)->count(2)->create();

    expect($user->usageLogs)->toHaveCount(2);
});

/*
|--------------------------------------------------------------------------
| Status Method Tests
|--------------------------------------------------------------------------
*/

test('isPending returns true for pending user', function () {
    $user = User::factory()->pending()->create();

    expect($user->isPending())->toBeTrue()
        ->and($user->isApproved())->toBeFalse()
        ->and($user->isRejected())->toBeFalse();
});

test('isApproved returns true for approved user', function () {
    $user = User::factory()->approved()->create();

    expect($user->isApproved())->toBeTrue()
        ->and($user->isPending())->toBeFalse()
        ->and($user->isRejected())->toBeFalse();
});

test('isRejected returns true for rejected user', function () {
    $user = User::factory()->rejected()->create();

    expect($user->isRejected())->toBeTrue()
        ->and($user->isPending())->toBeFalse()
        ->and($user->isApproved())->toBeFalse();
});

test('isSuspended returns true for suspended user', function () {
    $user = User::factory()->suspended()->create();

    expect($user->isSuspended())->toBeTrue();
});

test('canAccessApp returns true for approved users', function () {
    $approved = User::factory()->approved()->create();
    $pending = User::factory()->pending()->create();
    $rejected = User::factory()->rejected()->create();
    $suspended = User::factory()->suspended()->create();

    expect($approved->canAccessApp())->toBeTrue()
        ->and($pending->canAccessApp())->toBeFalse()
        ->and($rejected->canAccessApp())->toBeFalse()
        ->and($suspended->canAccessApp())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Status Change Method Tests
|--------------------------------------------------------------------------
*/

test('approve changes status to approved', function () {
    $user = User::factory()->pending()->create();

    $user->approve();

    expect($user->fresh()->status)->toBe(UserStatus::Approved);
});

test('reject changes status to rejected with reason', function () {
    $user = User::factory()->pending()->create();

    $user->reject('Does not meet requirements');

    $user->refresh();
    expect($user->status)->toBe(UserStatus::Rejected)
        ->and($user->status_reason)->toBe('Does not meet requirements');
});

test('suspend changes status to suspended', function () {
    $user = User::factory()->approved()->create();

    $user->suspend('Violation of terms');

    $user->refresh();
    expect($user->status)->toBe(UserStatus::Suspended)
        ->and($user->status_reason)->toBe('Violation of terms');
});

test('reactivate changes status to approved', function () {
    $user = User::factory()->suspended()->create();

    $user->reactivate();

    expect($user->fresh()->status)->toBe(UserStatus::Approved);
});

/*
|--------------------------------------------------------------------------
| Role Method Tests
|--------------------------------------------------------------------------
*/

test('isAdmin returns true for admin users', function () {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->approved()->create();

    expect($admin->isAdmin())->toBeTrue()
        ->and($member->isAdmin())->toBeFalse();
});

test('isMember returns true for member users', function () {
    $member = User::factory()->approved()->create();
    $admin = User::factory()->admin()->create();

    expect($member->isMember())->toBeTrue()
        ->and($admin->isMember())->toBeFalse();
});

test('promoteToAdmin changes role to admin', function () {
    $user = User::factory()->approved()->create();

    $user->promoteToAdmin();

    expect($user->fresh()->role)->toBe(UserRole::Admin);
});

test('demoteToMember changes role to member', function () {
    $user = User::factory()->admin()->create();

    $user->demoteToMember();

    expect($user->fresh()->role)->toBe(UserRole::Member);
});

/*
|--------------------------------------------------------------------------
| Query Scope Tests
|--------------------------------------------------------------------------
*/

test('pending scope returns only pending users', function () {
    User::factory()->pending()->count(2)->create();
    User::factory()->approved()->create();

    expect(User::pending()->count())->toBe(2);
});

test('approved scope returns only approved users', function () {
    User::factory()->approved()->count(3)->create();
    User::factory()->pending()->create();

    expect(User::approved()->count())->toBe(3);
});

test('admins scope returns only admin users', function () {
    User::factory()->admin()->count(2)->create();
    User::factory()->approved()->create();

    expect(User::admins()->count())->toBe(2);
});

test('members scope returns only member users', function () {
    User::factory()->approved()->count(3)->create();
    User::factory()->admin()->create();

    expect(User::members()->count())->toBe(3);
});

test('suspended scope returns only suspended users', function () {
    User::factory()->suspended()->count(2)->create();
    User::factory()->approved()->create();

    expect(User::suspended()->count())->toBe(2);
});

/*
|--------------------------------------------------------------------------
| Notification Preferences Tests
|--------------------------------------------------------------------------
*/

test('getNotificationSetting returns default when not set', function () {
    $user = User::factory()->create(['notification_preferences' => null]);

    expect($user->getNotificationSetting('email_reservation_confirmations'))->toBeTrue();
});

test('getNotificationSetting returns stored value', function () {
    $user = User::factory()->create([
        'notification_preferences' => ['email_reservation_confirmations' => false],
    ]);

    expect($user->getNotificationSetting('email_reservation_confirmations'))->toBeFalse();
});

test('setNotificationSetting updates preference', function () {
    $user = User::factory()->create();

    $user->setNotificationSetting('email_reservation_confirmations', false);

    expect($user->fresh()->getNotificationSetting('email_reservation_confirmations'))->toBeFalse();
});

