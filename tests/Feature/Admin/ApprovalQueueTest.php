<?php

/**
 * Tests for admin approval queue functionality.
 *
 * Verifies admin access control and approve/reject actions.
 */

use App\Livewire\Admin\ApprovalQueue;
use App\Models\User;
use App\Notifications\RegistrationApproved;
use App\Notifications\RegistrationRejected;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

test('guests cannot access approval queue', function () {
    $this->get(route('admin.approvals'))
        ->assertRedirect(route('login'));
});

test('non-admin users cannot access approval queue', function () {
    $user = createMember();

    $this->actingAs($user)
        ->get(route('admin.approvals'))
        ->assertStatus(403);
});

test('admin can access approval queue', function () {
    $admin = createAdmin();

    $this->actingAs($admin)
        ->get(route('admin.approvals'))
        ->assertStatus(200)
        ->assertSee('Approval Queue');
});

test('approval queue shows pending users', function () {
    $admin = createAdmin();
    $pendingUser = User::factory()->pending()->create(['name' => 'Pending Person']);
    User::factory()->approved()->create(['name' => 'Approved Person']);

    Livewire::actingAs($admin)
        ->test(ApprovalQueue::class)
        ->assertSee('Pending Person')
        ->assertDontSee('Approved Person');
});

test('admin can approve pending user', function () {
    $admin = createAdmin();
    $pendingUser = User::factory()->pending()->create();

    Livewire::actingAs($admin)
        ->test(ApprovalQueue::class)
        ->call('approve', $pendingUser->id)
        ->assertHasNoErrors();

    $pendingUser->refresh();
    expect($pendingUser->isApproved())->toBeTrue();
});

test('admin can reject pending user', function () {
    $admin = createAdmin();
    $pendingUser = User::factory()->pending()->create();

    Livewire::actingAs($admin)
        ->test(ApprovalQueue::class)
        ->call('openRejectModal', $pendingUser->id)
        ->set('rejectionReason', 'Does not meet requirements')
        ->call('reject')
        ->assertHasNoErrors();

    $pendingUser->refresh();
    expect($pendingUser->isRejected())->toBeTrue()
        ->and($pendingUser->status_reason)->toBe('Does not meet requirements');
});

test('admin can reject without reason', function () {
    $admin = createAdmin();
    $pendingUser = User::factory()->pending()->create();

    Livewire::actingAs($admin)
        ->test(ApprovalQueue::class)
        ->call('openRejectModal', $pendingUser->id)
        ->call('reject')
        ->assertHasNoErrors();

    $pendingUser->refresh();
    expect($pendingUser->isRejected())->toBeTrue()
        ->and($pendingUser->status_reason)->toBeNull();
});

test('approval queue can be searched', function () {
    $admin = createAdmin();
    User::factory()->pending()->create(['name' => 'John Smith', 'email' => 'john@example.com']);
    User::factory()->pending()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

    Livewire::actingAs($admin)
        ->test(ApprovalQueue::class)
        ->assertSee('John Smith')
        ->assertSee('Jane Doe')
        ->set('search', 'John')
        ->assertSee('John Smith')
        ->assertDontSee('Jane Doe');
});

test('empty queue shows success message', function () {
    $admin = createAdmin();

    Livewire::actingAs($admin)
        ->test(ApprovalQueue::class)
        ->assertSee('All caught up!');
});

test('approval sends notification to user', function () {
    Notification::fake();

    $admin = createAdmin();
    $pendingUser = User::factory()->pending()->create();

    Livewire::actingAs($admin)
        ->test(ApprovalQueue::class)
        ->call('approve', $pendingUser->id);

    Notification::assertSentTo($pendingUser, RegistrationApproved::class);
});

test('rejection sends notification to user', function () {
    Notification::fake();

    $admin = createAdmin();
    $pendingUser = User::factory()->pending()->create();

    Livewire::actingAs($admin)
        ->test(ApprovalQueue::class)
        ->call('openRejectModal', $pendingUser->id)
        ->set('rejectionReason', 'Test reason')
        ->call('reject');

    Notification::assertSentTo($pendingUser, RegistrationRejected::class, function ($notification) {
        return $notification->reason === 'Test reason';
    });
});
