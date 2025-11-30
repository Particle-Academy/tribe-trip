<?php

/**
 * Tests for admin invitation system.
 *
 * Covers invitation creation, management, and registration flow.
 */

use App\Enums\InvitationStatus;
use App\Enums\UserStatus;
use App\Livewire\Admin\CreateInvitation;
use App\Livewire\Admin\InvitationList;
use App\Livewire\Auth\RegisterWithInvitation;
use App\Models\Invitation;
use App\Models\User;
use App\Notifications\InvitationSent;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

/*
|--------------------------------------------------------------------------
| Access Control Tests
|--------------------------------------------------------------------------
*/

test('guests cannot access invitation pages', function () {
    $this->get(route('admin.invitations'))->assertRedirect(route('login'));
    $this->get(route('admin.invitations.create'))->assertRedirect(route('login'));
});

test('non-admin users cannot access invitation pages', function () {
    $user = createMember();

    $this->actingAs($user)->get(route('admin.invitations'))->assertStatus(403);
    $this->actingAs($user)->get(route('admin.invitations.create'))->assertStatus(403);
});

test('admin can access invitation pages', function () {
    $admin = createAdmin();

    $this->actingAs($admin)->get(route('admin.invitations'))->assertStatus(200);
    $this->actingAs($admin)->get(route('admin.invitations.create'))->assertStatus(200);
});

/*
|--------------------------------------------------------------------------
| Invitation Creation Tests
|--------------------------------------------------------------------------
*/

test('admin can create invitation', function () {
    Notification::fake();
    $admin = createAdmin();

    Livewire::actingAs($admin)
        ->test(CreateInvitation::class)
        ->set('email', 'invite@example.com')
        ->set('name', 'John Doe')
        ->set('expiresInDays', 7)
        ->set('sendEmail', true)
        ->call('create')
        ->assertSet('showSuccess', true);

    $this->assertDatabaseHas('invitations', [
        'email' => 'invite@example.com',
        'name' => 'John Doe',
        'status' => InvitationStatus::Pending->value,
        'invited_by' => $admin->id,
    ]);

    Notification::assertSentOnDemand(InvitationSent::class);
});

test('admin can create invitation without sending email', function () {
    Notification::fake();
    $admin = createAdmin();

    Livewire::actingAs($admin)
        ->test(CreateInvitation::class)
        ->set('email', 'nosend@example.com')
        ->set('sendEmail', false)
        ->call('create')
        ->assertSet('showSuccess', true);

    $this->assertDatabaseHas('invitations', [
        'email' => 'nosend@example.com',
    ]);

    Notification::assertNothingSent();
});

test('cannot create duplicate pending invitation', function () {
    $admin = createAdmin();
    Invitation::factory()->pending()->create([
        'email' => 'existing@example.com',
        'invited_by' => $admin->id,
    ]);

    Livewire::actingAs($admin)
        ->test(CreateInvitation::class)
        ->set('email', 'existing@example.com')
        ->call('create')
        ->assertHasErrors(['email']);
});

/*
|--------------------------------------------------------------------------
| Invitation Management Tests
|--------------------------------------------------------------------------
*/

test('admin can view invitation list', function () {
    $admin = createAdmin();
    Invitation::factory()->pending()->create([
        'email' => 'test@example.com',
        'invited_by' => $admin->id,
    ]);

    Livewire::actingAs($admin)
        ->test(InvitationList::class)
        ->assertSee('test@example.com');
});

test('admin can revoke pending invitation', function () {
    $admin = createAdmin();
    $invitation = Invitation::factory()->pending()->create([
        'invited_by' => $admin->id,
    ]);

    Livewire::actingAs($admin)
        ->test(InvitationList::class)
        ->call('revoke', $invitation->id);

    $invitation->refresh();
    expect($invitation->isRevoked())->toBeTrue();
});

test('admin can filter invitations by status', function () {
    $admin = createAdmin();
    Invitation::factory()->pending()->create([
        'email' => 'pending@example.com',
        'invited_by' => $admin->id,
    ]);
    Invitation::factory()->accepted()->create([
        'email' => 'accepted@example.com',
        'invited_by' => $admin->id,
    ]);

    Livewire::actingAs($admin)
        ->test(InvitationList::class)
        ->assertSee('pending@example.com')
        ->assertSee('accepted@example.com')
        ->set('status', 'pending')
        ->assertSee('pending@example.com')
        ->assertDontSee('accepted@example.com');
});

/*
|--------------------------------------------------------------------------
| Invitation Registration Tests
|--------------------------------------------------------------------------
*/

test('valid invitation shows registration form', function () {
    $admin = createAdmin();
    $invitation = Invitation::factory()->pending()->create([
        'email' => 'invitee@example.com',
        'name' => 'Invited Person',
        'invited_by' => $admin->id,
    ]);

    Livewire::test(RegisterWithInvitation::class, ['token' => $invitation->token])
        ->assertSet('isValid', true)
        ->assertSet('name', 'Invited Person')
        ->assertSee('invitee@example.com');
});

test('expired invitation shows error', function () {
    $admin = createAdmin();
    $invitation = Invitation::factory()->expired()->create([
        'invited_by' => $admin->id,
    ]);

    Livewire::test(RegisterWithInvitation::class, ['token' => $invitation->token])
        ->assertSet('isValid', false)
        ->assertSee('expired');
});

test('revoked invitation shows error', function () {
    $admin = createAdmin();
    $invitation = Invitation::factory()->revoked()->create([
        'invited_by' => $admin->id,
    ]);

    Livewire::test(RegisterWithInvitation::class, ['token' => $invitation->token])
        ->assertSet('isValid', false)
        ->assertSee('revoked');
});

test('invalid token shows error', function () {
    Livewire::test(RegisterWithInvitation::class, ['token' => 'invalid-token'])
        ->assertSet('isValid', false)
        ->assertSee('invalid');
});

test('user can register via invitation', function () {
    $admin = createAdmin();
    $invitation = Invitation::factory()->pending()->create([
        'email' => 'newuser@example.com',
        'invited_by' => $admin->id,
    ]);

    Livewire::test(RegisterWithInvitation::class, ['token' => $invitation->token])
        ->set('name', 'New User')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('register')
        ->assertRedirect(route('dashboard'));

    // User should be created and approved
    $this->assertDatabaseHas('users', [
        'email' => 'newuser@example.com',
        'name' => 'New User',
        'status' => UserStatus::Approved->value,
    ]);

    // Invitation should be marked as accepted
    $invitation->refresh();
    expect($invitation->isAccepted())->toBeTrue()
        ->and($invitation->accepted_by)->not->toBeNull();
});

test('invited user is automatically logged in', function () {
    $admin = createAdmin();
    $invitation = Invitation::factory()->pending()->create([
        'email' => 'autologin@example.com',
        'invited_by' => $admin->id,
    ]);

    Livewire::test(RegisterWithInvitation::class, ['token' => $invitation->token])
        ->set('name', 'Auto Login User')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('register');

    $this->assertAuthenticated();
});
