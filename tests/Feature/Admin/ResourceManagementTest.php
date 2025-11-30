<?php

/**
 * Tests for admin resource management.
 *
 * Covers resource CRUD operations, status management, and listing.
 */

use App\Enums\PricingModel;
use App\Enums\PricingUnit;
use App\Enums\ResourceStatus;
use App\Enums\ResourceType;
use App\Livewire\Admin\ResourceForm;
use App\Livewire\Admin\ResourceList;
use App\Models\Resource;
use Livewire\Livewire;

/*
|--------------------------------------------------------------------------
| Access Control Tests
|--------------------------------------------------------------------------
*/

test('guests cannot access resource management pages', function () {
    $this->get(route('admin.resources'))->assertRedirect(route('login'));
    $this->get(route('admin.resources.create'))->assertRedirect(route('login'));
});

test('non-admin users cannot access resource management pages', function () {
    $user = createMember();

    $this->actingAs($user)->get(route('admin.resources'))->assertStatus(403);
    $this->actingAs($user)->get(route('admin.resources.create'))->assertStatus(403);
});

test('admin can access resource management pages', function () {
    $admin = createAdmin();

    $this->actingAs($admin)->get(route('admin.resources'))->assertStatus(200);
    $this->actingAs($admin)->get(route('admin.resources.create'))->assertStatus(200);
});

/*
|--------------------------------------------------------------------------
| Resource List Tests
|--------------------------------------------------------------------------
*/

test('resource list shows all resources', function () {
    $admin = createAdmin();
    Resource::factory()->create(['name' => 'Community Van']);
    Resource::factory()->create(['name' => 'Pressure Washer']);

    Livewire::actingAs($admin)
        ->test(ResourceList::class)
        ->assertSee('Community Van')
        ->assertSee('Pressure Washer');
});

test('resource list can filter by type', function () {
    $admin = createAdmin();
    Resource::factory()->vehicle()->create(['name' => 'Club Van']);
    Resource::factory()->equipment()->create(['name' => 'Generator']);

    Livewire::actingAs($admin)
        ->test(ResourceList::class)
        ->assertSee('Club Van')
        ->assertSee('Generator')
        ->set('type', ResourceType::Vehicle->value)
        ->assertSee('Club Van')
        ->assertDontSee('Generator');
});

test('resource list can filter by status', function () {
    $admin = createAdmin();
    Resource::factory()->create(['name' => 'Active Resource', 'status' => ResourceStatus::Active]);
    Resource::factory()->maintenance()->create(['name' => 'Maintenance Resource']);

    Livewire::actingAs($admin)
        ->test(ResourceList::class)
        ->assertSee('Active Resource')
        ->assertSee('Maintenance Resource')
        ->set('status', ResourceStatus::Maintenance->value)
        ->assertDontSee('Active Resource')
        ->assertSee('Maintenance Resource');
});

test('resource list can search by name', function () {
    $admin = createAdmin();
    Resource::factory()->create(['name' => 'Community Van']);
    Resource::factory()->create(['name' => 'Pickup Truck']);

    Livewire::actingAs($admin)
        ->test(ResourceList::class)
        ->set('search', 'Van')
        ->assertSee('Community Van')
        ->assertDontSee('Pickup Truck');
});

/*
|--------------------------------------------------------------------------
| Resource Creation Tests
|--------------------------------------------------------------------------
*/

test('admin can create resource with flat fee pricing', function () {
    $admin = createAdmin();

    Livewire::actingAs($admin)
        ->test(ResourceForm::class)
        ->set('name', 'New Van')
        ->set('description', 'A nice community van')
        ->set('type', ResourceType::Vehicle->value)
        ->set('pricing_model', PricingModel::FlatFee->value)
        ->set('rate', 50.00)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('resources', [
        'name' => 'New Van',
        'type' => ResourceType::Vehicle->value,
        'pricing_model' => PricingModel::FlatFee->value,
        'rate' => 50.00,
    ]);
});

test('admin can create resource with per-unit pricing', function () {
    $admin = createAdmin();

    Livewire::actingAs($admin)
        ->test(ResourceForm::class)
        ->set('name', 'Mileage Van')
        ->set('description', 'Charged per mile')
        ->set('type', ResourceType::Vehicle->value)
        ->set('pricing_model', PricingModel::PerUnit->value)
        ->set('rate', 0.50)
        ->set('pricing_unit', PricingUnit::Mile->value)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('resources', [
        'name' => 'Mileage Van',
        'pricing_model' => PricingModel::PerUnit->value,
        'pricing_unit' => PricingUnit::Mile->value,
        'rate' => 0.50,
    ]);
});

test('resource creation requires name', function () {
    $admin = createAdmin();

    Livewire::actingAs($admin)
        ->test(ResourceForm::class)
        ->set('name', '')
        ->set('type', ResourceType::Vehicle->value)
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

test('resource creation requires type', function () {
    $admin = createAdmin();

    Livewire::actingAs($admin)
        ->test(ResourceForm::class)
        ->set('name', 'Test Resource')
        ->set('type', '')
        ->call('save')
        ->assertHasErrors(['type' => 'required']);
});

/*
|--------------------------------------------------------------------------
| Resource Editing Tests
|--------------------------------------------------------------------------
*/

test('admin can edit existing resource', function () {
    $admin = createAdmin();
    $resource = Resource::factory()->create(['name' => 'Old Name']);

    Livewire::actingAs($admin)
        ->test(ResourceForm::class, ['resource' => $resource])
        ->assertSet('name', 'Old Name')
        ->set('name', 'New Name')
        ->call('save')
        ->assertHasNoErrors();

    $resource->refresh();
    expect($resource->name)->toBe('New Name');
});

test('admin can access resource edit page', function () {
    $admin = createAdmin();
    $resource = Resource::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.resources.edit', $resource))
        ->assertStatus(200);
});

/*
|--------------------------------------------------------------------------
| Resource Status Management Tests
|--------------------------------------------------------------------------
*/

test('admin can deactivate resource', function () {
    $admin = createAdmin();
    $resource = Resource::factory()->create(['status' => ResourceStatus::Active]);

    Livewire::actingAs($admin)
        ->test(ResourceList::class)
        ->call('deactivate', $resource->id);

    $resource->refresh();
    expect($resource->status)->toBe(ResourceStatus::Inactive);
});

test('admin can activate resource', function () {
    $admin = createAdmin();
    $resource = Resource::factory()->inactive()->create();

    Livewire::actingAs($admin)
        ->test(ResourceList::class)
        ->call('activate', $resource->id);

    $resource->refresh();
    expect($resource->status)->toBe(ResourceStatus::Active);
});

test('admin can mark resource for maintenance', function () {
    $admin = createAdmin();
    $resource = Resource::factory()->create(['status' => ResourceStatus::Active]);

    Livewire::actingAs($admin)
        ->test(ResourceList::class)
        ->call('markMaintenance', $resource->id);

    $resource->refresh();
    expect($resource->status)->toBe(ResourceStatus::Maintenance);
});

