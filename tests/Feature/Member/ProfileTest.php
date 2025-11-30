<?php

/**
 * Tests for member profile management.
 *
 * Covers profile viewing and editing functionality.
 */

use App\Livewire\Member\Profile;
use App\Models\User;
use Livewire\Livewire;

/*
|--------------------------------------------------------------------------
| Access Control Tests
|--------------------------------------------------------------------------
*/

test('guests cannot access profile page', function () {
    $this->get(route('member.profile'))->assertRedirect(route('login'));
});

test('pending users cannot access profile page', function () {
    $user = User::factory()->pending()->create();

    // Pending users are redirected to the pending approval page
    $this->actingAs($user)
        ->get(route('member.profile'))
        ->assertRedirect(route('register.pending'));
});

test('approved members can access profile page', function () {
    $member = createMember();

    $this->actingAs($member)
        ->get(route('member.profile'))
        ->assertStatus(200);
});

/*
|--------------------------------------------------------------------------
| Profile View Tests
|--------------------------------------------------------------------------
*/

test('profile page shows user information', function () {
    $member = User::factory()->approved()->create([
        'name' => 'Test Member',
        'email' => 'test@example.com',
        'phone' => '555-123-4567',
    ]);

    Livewire::actingAs($member)
        ->test(Profile::class)
        ->assertSee('Test Member')
        ->assertSee('test@example.com')
        ->assertSee('555-123-4567');
});

/*
|--------------------------------------------------------------------------
| Profile Edit Tests
|--------------------------------------------------------------------------
*/

test('member can update their name', function () {
    $member = createMember();

    // Profile uses saveProfile() method
    Livewire::actingAs($member)
        ->test(Profile::class)
        ->set('name', 'Updated Name')
        ->call('saveProfile')
        ->assertHasNoErrors();

    $member->refresh();
    expect($member->name)->toBe('Updated Name');
});

test('member can update their phone', function () {
    $member = createMember();

    // Profile uses saveProfile() method
    Livewire::actingAs($member)
        ->test(Profile::class)
        ->set('phone', '555-999-8888')
        ->call('saveProfile')
        ->assertHasNoErrors();

    $member->refresh();
    expect($member->phone)->toBe('555-999-8888');
});

test('member cannot change email to existing email', function () {
    User::factory()->create(['email' => 'existing@example.com']);
    $member = createMember();

    // Profile uses saveProfile() method
    Livewire::actingAs($member)
        ->test(Profile::class)
        ->set('email', 'existing@example.com')
        ->call('saveProfile')
        ->assertHasErrors(['email']);
});

test('name is required for profile update', function () {
    $member = createMember();

    // Profile uses saveProfile() method
    Livewire::actingAs($member)
        ->test(Profile::class)
        ->set('name', '')
        ->call('saveProfile')
        ->assertHasErrors(['name' => 'required']);
});

test('email is required for profile update', function () {
    $member = createMember();

    // Profile uses saveProfile() method
    Livewire::actingAs($member)
        ->test(Profile::class)
        ->set('email', '')
        ->call('saveProfile')
        ->assertHasErrors(['email' => 'required']);
});

/*
|--------------------------------------------------------------------------
| Notification Preferences Tests
|--------------------------------------------------------------------------
*/

test('member can update notification preferences', function () {
    $member = createMember();

    // Profile uses individual boolean properties and saveNotifications() method
    Livewire::actingAs($member)
        ->test(Profile::class)
        ->set('email_reservation_confirmations', false)
        ->call('saveNotifications')
        ->assertHasNoErrors();

    $member->refresh();
    expect($member->getNotificationSetting('email_reservation_confirmations'))->toBeFalse();
});

