<?php

/**
 * Tests for user registration flow.
 *
 * Verifies registration form validation, user creation with pending status,
 * and proper redirection to pending approval page.
 */

use App\Enums\UserStatus;
use App\Livewire\Auth\Register;
use App\Models\User;
use App\Notifications\NewRegistrationAlert;
use App\Notifications\RegistrationReceived;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

test('registration page is accessible', function () {
    $this->get(route('register'))
        ->assertStatus(200)
        ->assertSee('Join the Community');
});

test('user can register with valid data', function () {
    Livewire::test(Register::class)
        ->set('form.name', 'Test User')
        ->set('form.email', 'test@example.com')
        ->set('form.phone', '555-123-4567')
        ->set('form.password', 'password123')
        ->set('form.password_confirmation', 'password123')
        ->call('register')
        ->assertRedirect(route('register.pending'));

    $this->assertDatabaseHas('users', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '555-123-4567',
        'status' => UserStatus::Pending->value,
    ]);
});

test('user is created with pending status', function () {
    Livewire::test(Register::class)
        ->set('form.name', 'Pending User')
        ->set('form.email', 'pending@example.com')
        ->set('form.password', 'password123')
        ->set('form.password_confirmation', 'password123')
        ->call('register');

    $user = User::where('email', 'pending@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->isPending())->toBeTrue()
        ->and($user->canAccessApp())->toBeFalse();
});

test('registration requires name', function () {
    Livewire::test(Register::class)
        ->set('form.name', '')
        ->set('form.email', 'test@example.com')
        ->set('form.password', 'password123')
        ->set('form.password_confirmation', 'password123')
        ->call('register')
        ->assertHasErrors(['form.name' => 'required']);
});

test('registration requires valid email', function () {
    Livewire::test(Register::class)
        ->set('form.name', 'Test User')
        ->set('form.email', 'invalid-email')
        ->set('form.password', 'password123')
        ->set('form.password_confirmation', 'password123')
        ->call('register')
        ->assertHasErrors(['form.email' => 'email']);
});

test('registration requires unique email', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    Livewire::test(Register::class)
        ->set('form.name', 'Test User')
        ->set('form.email', 'existing@example.com')
        ->set('form.password', 'password123')
        ->set('form.password_confirmation', 'password123')
        ->call('register')
        ->assertHasErrors(['form.email' => 'unique']);
});

test('registration requires password minimum length', function () {
    Livewire::test(Register::class)
        ->set('form.name', 'Test User')
        ->set('form.email', 'test@example.com')
        ->set('form.password', 'short')
        ->set('form.password_confirmation', 'short')
        ->call('register')
        ->assertHasErrors(['form.password' => 'min']);
});

test('registration requires password confirmation', function () {
    Livewire::test(Register::class)
        ->set('form.name', 'Test User')
        ->set('form.email', 'test@example.com')
        ->set('form.password', 'password123')
        ->set('form.password_confirmation', 'different123')
        ->call('register')
        ->assertHasErrors(['form.password' => 'confirmed']);
});

test('phone is optional', function () {
    Livewire::test(Register::class)
        ->set('form.name', 'Test User')
        ->set('form.email', 'nophone@example.com')
        ->set('form.phone', '')
        ->set('form.password', 'password123')
        ->set('form.password_confirmation', 'password123')
        ->call('register')
        ->assertRedirect(route('register.pending'));

    $this->assertDatabaseHas('users', [
        'email' => 'nophone@example.com',
        'phone' => null,
    ]);
});

test('pending approval page is accessible', function () {
    $this->get(route('register.pending'))
        ->assertStatus(200)
        ->assertSee('Registration Received');
});

test('registration sends confirmation email to user', function () {
    Notification::fake();

    Livewire::test(Register::class)
        ->set('form.name', 'Email Test User')
        ->set('form.email', 'emailtest@example.com')
        ->set('form.password', 'password123')
        ->set('form.password_confirmation', 'password123')
        ->call('register');

    $user = User::where('email', 'emailtest@example.com')->first();

    Notification::assertSentTo($user, RegistrationReceived::class);
});

test('registration alerts admins of new registration', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();

    Livewire::test(Register::class)
        ->set('form.name', 'New Member')
        ->set('form.email', 'newmember@example.com')
        ->set('form.password', 'password123')
        ->set('form.password_confirmation', 'password123')
        ->call('register');

    Notification::assertSentTo($admin, NewRegistrationAlert::class);
});
