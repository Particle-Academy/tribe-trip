<?php

/**
 * Tests for member resource catalog browsing.
 *
 * Covers resource listing, filtering, and detail views.
 */

use App\Enums\ResourceStatus;
use App\Enums\ResourceType;
use App\Livewire\Member\ResourceCatalog;
use App\Livewire\Member\ResourceDetail;
use App\Models\Resource;
use App\Models\User;
use Livewire\Livewire;

/*
|--------------------------------------------------------------------------
| Access Control Tests
|--------------------------------------------------------------------------
*/

test('guests cannot access resource catalog', function () {
    $this->get(route('member.resources'))->assertRedirect(route('login'));
});

test('pending users cannot access resource catalog', function () {
    $user = User::factory()->pending()->create();

    // Pending users are redirected to the pending approval page
    $this->actingAs($user)
        ->get(route('member.resources'))
        ->assertRedirect(route('register.pending'));
});

test('approved members can access resource catalog', function () {
    $member = createMember();

    $this->actingAs($member)
        ->get(route('member.resources'))
        ->assertStatus(200);
});

/*
|--------------------------------------------------------------------------
| Resource Listing Tests
|--------------------------------------------------------------------------
*/

test('catalog shows active resources', function () {
    $member = createMember();
    Resource::factory()->create(['name' => 'Community Van', 'status' => ResourceStatus::Active]);
    Resource::factory()->inactive()->create(['name' => 'Inactive Van']);

    Livewire::actingAs($member)
        ->test(ResourceCatalog::class)
        ->assertSee('Community Van')
        ->assertDontSee('Inactive Van');
});

test('catalog does not show resources under maintenance', function () {
    $member = createMember();
    // Catalog only shows active resources - maintenance resources are hidden
    Resource::factory()->maintenance()->create(['name' => 'Maintenance Van']);
    Resource::factory()->create(['name' => 'Active Van']);

    Livewire::actingAs($member)
        ->test(ResourceCatalog::class)
        ->assertSee('Active Van')
        ->assertDontSee('Maintenance Van');
});

test('catalog can filter by resource type', function () {
    $member = createMember();
    Resource::factory()->vehicle()->create(['name' => 'Club Van']);
    Resource::factory()->equipment()->create(['name' => 'Generator']);

    Livewire::actingAs($member)
        ->test(ResourceCatalog::class)
        ->assertSee('Club Van')
        ->assertSee('Generator')
        ->set('type', ResourceType::Vehicle->value)
        ->assertSee('Club Van')
        ->assertDontSee('Generator');
});

test('catalog can search by name', function () {
    $member = createMember();
    Resource::factory()->create(['name' => 'Community Van']);
    Resource::factory()->create(['name' => 'Pickup Truck']);

    Livewire::actingAs($member)
        ->test(ResourceCatalog::class)
        ->set('search', 'Van')
        ->assertSee('Community Van')
        ->assertDontSee('Pickup Truck');
});

test('catalog shows pricing information', function () {
    $member = createMember();
    Resource::factory()->flatFee(25.00)->create(['name' => 'Flat Fee Resource']);

    Livewire::actingAs($member)
        ->test(ResourceCatalog::class)
        ->assertSee('$25.00');
});

/*
|--------------------------------------------------------------------------
| Resource Detail Tests
|--------------------------------------------------------------------------
*/

test('member can view resource details', function () {
    $member = createMember();
    $resource = Resource::factory()->create([
        'name' => 'Detailed Van',
        'description' => 'A detailed description of the van.',
    ]);

    $this->actingAs($member)
        ->get(route('member.resources.show', $resource))
        ->assertStatus(200);

    Livewire::actingAs($member)
        ->test(ResourceDetail::class, ['resource' => $resource])
        ->assertSee('Detailed Van')
        ->assertSee('A detailed description of the van.');
});

test('resource detail shows pricing breakdown', function () {
    $member = createMember();
    $resource = Resource::factory()->perHour(15.00)->create();

    Livewire::actingAs($member)
        ->test(ResourceDetail::class, ['resource' => $resource])
        ->assertSee('$15.00')
        ->assertSee('/hr');
});

test('cannot view inactive resource details', function () {
    $member = createMember();
    $resource = Resource::factory()->inactive()->create();

    $this->actingAs($member)
        ->get(route('member.resources.show', $resource))
        ->assertStatus(404);
});

