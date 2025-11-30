<?php

/**
 * Tests for admin member management.
 *
 * Covers member list, detail view, editing, and status management.
 */

use App\Livewire\Admin\MemberDetail;
use App\Livewire\Admin\MemberList;
use App\Models\User;
use Livewire\Livewire;

/*
|--------------------------------------------------------------------------
| Access Control Tests
|--------------------------------------------------------------------------
*/

test('guests cannot access member pages', function () {
    $member = createMember();

    $this->get(route('admin.members'))->assertRedirect(route('login'));
    $this->get(route('admin.members.show', $member))->assertRedirect(route('login'));
});

test('non-admin users cannot access member pages', function () {
    $user = createMember();
    $member = createMember();

    $this->actingAs($user)->get(route('admin.members'))->assertStatus(403);
    $this->actingAs($user)->get(route('admin.members.show', $member))->assertStatus(403);
});

test('admin can access member pages', function () {
    $admin = createAdmin();
    $member = createMember();

    $this->actingAs($admin)->get(route('admin.members'))->assertStatus(200);
    $this->actingAs($admin)->get(route('admin.members.show', $member))->assertStatus(200);
});

/*
|--------------------------------------------------------------------------
| Member List Tests
|--------------------------------------------------------------------------
*/

test('member list shows all members', function () {
    $admin = createAdmin();
    User::factory()->approved()->create(['name' => 'John Doe']);
    User::factory()->approved()->create(['name' => 'Jane Smith']);

    Livewire::actingAs($admin)
        ->test(MemberList::class)
        ->assertSee('John Doe')
        ->assertSee('Jane Smith');
});

test('member list can search by name', function () {
    $admin = createAdmin();
    User::factory()->approved()->create(['name' => 'John Doe']);
    User::factory()->approved()->create(['name' => 'Jane Smith']);

    Livewire::actingAs($admin)
        ->test(MemberList::class)
        ->set('search', 'John')
        ->assertSee('John Doe')
        ->assertDontSee('Jane Smith');
});

test('member list can search by email', function () {
    $admin = createAdmin();
    User::factory()->approved()->create(['email' => 'john@example.com']);
    User::factory()->approved()->create(['email' => 'jane@example.com']);

    Livewire::actingAs($admin)
        ->test(MemberList::class)
        ->set('search', 'john@')
        ->assertSee('john@example.com')
        ->assertDontSee('jane@example.com');
});

test('member list can filter by status', function () {
    $admin = createAdmin();
    User::factory()->approved()->create(['name' => 'Active User']);
    User::factory()->suspended()->create(['name' => 'Suspended User']);

    Livewire::actingAs($admin)
        ->test(MemberList::class)
        ->assertSee('Active User')
        ->assertSee('Suspended User')
        ->set('status', 'suspended')
        ->assertDontSee('Active User')
        ->assertSee('Suspended User');
});

test('member list can filter by role', function () {
    $admin = User::factory()->admin()->create(['name' => 'Admin User']);
    User::factory()->approved()->create(['name' => 'Regular Member']);

    Livewire::actingAs($admin)
        ->test(MemberList::class)
        ->set('role', 'member')
        ->assertDontSee('Admin User')
        ->assertSee('Regular Member');
});

/*
|--------------------------------------------------------------------------
| Member Detail Tests
|--------------------------------------------------------------------------
*/

test('member detail shows user info', function () {
    $admin = createAdmin();
    $member = User::factory()->approved()->create([
        'name' => 'Test Member',
        'email' => 'test@example.com',
        'phone' => '555-123-4567',
    ]);

    Livewire::actingAs($admin)
        ->test(MemberDetail::class, ['user' => $member])
        ->assertSee('Test Member')
        ->assertSee('test@example.com')
        ->assertSee('555-123-4567');
});

test('admin can edit member info', function () {
    $admin = createAdmin();
    $member = User::factory()->approved()->create([
        'name' => 'Old Name',
        'email' => 'old@example.com',
    ]);

    Livewire::actingAs($admin)
        ->test(MemberDetail::class, ['user' => $member])
        ->call('startEditing')
        ->set('name', 'New Name')
        ->set('email', 'new@example.com')
        ->call('save');

    $member->refresh();
    expect($member->name)->toBe('New Name')
        ->and($member->email)->toBe('new@example.com');
});

/*
|--------------------------------------------------------------------------
| Status Management Tests
|--------------------------------------------------------------------------
*/

test('admin can suspend member', function () {
    $admin = createAdmin();
    $member = createMember();

    Livewire::actingAs($admin)
        ->test(MemberDetail::class, ['user' => $member])
        ->call('openStatusModal', 'suspend')
        ->set('statusReason', 'Violation of rules')
        ->call('confirmStatusChange');

    $member->refresh();
    expect($member->isSuspended())->toBeTrue()
        ->and($member->status_reason)->toBe('Violation of rules');
});

test('admin can reactivate suspended member', function () {
    $admin = createAdmin();
    $member = User::factory()->suspended()->create();

    Livewire::actingAs($admin)
        ->test(MemberDetail::class, ['user' => $member])
        ->call('openStatusModal', 'reactivate')
        ->call('confirmStatusChange');

    $member->refresh();
    expect($member->isApproved())->toBeTrue();
});

test('admin cannot suspend themselves', function () {
    $admin = createAdmin();

    Livewire::actingAs($admin)
        ->test(MemberDetail::class, ['user' => $admin])
        ->call('openStatusModal', 'suspend')
        ->call('confirmStatusChange')
        ->assertHasNoErrors();

    $admin->refresh();
    expect($admin->isApproved())->toBeTrue(); // Status unchanged
});

test('admin can quick suspend from list', function () {
    $admin = createAdmin();
    $member = createMember();

    Livewire::actingAs($admin)
        ->test(MemberList::class)
        ->call('suspend', $member->id);

    $member->refresh();
    expect($member->isSuspended())->toBeTrue();
});

test('admin can quick reactivate from list', function () {
    $admin = createAdmin();
    $member = User::factory()->suspended()->create();

    Livewire::actingAs($admin)
        ->test(MemberList::class)
        ->call('reactivate', $member->id);

    $member->refresh();
    expect($member->isApproved())->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Role Management Tests
|--------------------------------------------------------------------------
*/

test('admin can promote member to admin', function () {
    $admin = createAdmin();
    $member = createMember();

    Livewire::actingAs($admin)
        ->test(MemberDetail::class, ['user' => $member])
        ->call('openRoleModal', 'promote')
        ->call('confirmRoleChange');

    $member->refresh();
    expect($member->isAdmin())->toBeTrue();
});

test('admin can demote admin to member', function () {
    $admin = createAdmin();
    $otherAdmin = createAdmin();

    Livewire::actingAs($admin)
        ->test(MemberDetail::class, ['user' => $otherAdmin])
        ->call('openRoleModal', 'demote')
        ->call('confirmRoleChange');

    $otherAdmin->refresh();
    expect($otherAdmin->isMember())->toBeTrue();
});

test('admin cannot change own role', function () {
    $admin = createAdmin();

    Livewire::actingAs($admin)
        ->test(MemberDetail::class, ['user' => $admin])
        ->call('openRoleModal', 'demote')
        ->call('confirmRoleChange');

    $admin->refresh();
    expect($admin->isAdmin())->toBeTrue(); // Role unchanged
});
