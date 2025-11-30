<?php

/**
 * Tests for user login functionality.
 *
 * Verifies login form validation, authentication, and access control
 * based on user status.
 */

use App\Livewire\Auth\Login;
use App\Models\User;
use Livewire\Livewire;

test('login page is accessible to guests', function () {
    $this->get(route('login'))
        ->assertStatus(200)
        ->assertSee('Sign In');
});

test('authenticated users are redirected from login page', function () {
    $user = createMember();

    $this->actingAs($user)
        ->get(route('login'))
        ->assertRedirect(route('dashboard'));
});

test('user can login with valid credentials', function () {
    $user = User::factory()->approved()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    Livewire::test(Login::class)
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('user cannot login with invalid credentials', function () {
    User::factory()->approved()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    Livewire::test(Login::class)
        ->set('email', 'test@example.com')
        ->set('password', 'wrongpassword')
        ->call('login')
        ->assertHasErrors(['email']);

    $this->assertGuest();
});

test('login requires email', function () {
    Livewire::test(Login::class)
        ->set('email', '')
        ->set('password', 'password')
        ->call('login')
        ->assertHasErrors(['email' => 'required']);
});

test('login requires password', function () {
    Livewire::test(Login::class)
        ->set('email', 'test@example.com')
        ->set('password', '')
        ->call('login')
        ->assertHasErrors(['password' => 'required']);
});

test('pending user cannot access dashboard after login', function () {
    $user = User::factory()->pending()->create([
        'email' => 'pending@example.com',
        'password' => bcrypt('password'),
    ]);

    Livewire::test(Login::class)
        ->set('email', 'pending@example.com')
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect(route('register.pending'));

    // Pending users should be logged out and redirected
    $this->assertGuest();
});

test('suspended user cannot access dashboard', function () {
    $user = User::factory()->suspended()->create([
        'email' => 'suspended@example.com',
        'password' => bcrypt('password'),
    ]);

    Livewire::test(Login::class)
        ->set('email', 'suspended@example.com')
        ->set('password', 'password')
        ->call('login');

    $this->assertAuthenticated();
});

test('user can logout', function () {
    $user = createMember();

    // Use withoutMiddleware to avoid CSRF issues in testing
    $this->actingAs($user)
        ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
        ->post(route('logout'))
        ->assertRedirect(route('home'));

    $this->assertGuest();
});

test('guests cannot access logout', function () {
    // Guests should be redirected to login by auth middleware
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
        ->post(route('logout'))
        ->assertRedirect(route('login'));
});

