<?php

/**
 * Unit tests for the Resource model.
 *
 * Tests relationships, status methods, pricing, and scopes.
 */

use App\Enums\PricingModel;
use App\Enums\ResourceStatus;
use App\Enums\ResourceType;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\ResourceImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Relationship Tests
|--------------------------------------------------------------------------
*/

test('resource has many images', function () {
    $resource = Resource::factory()->create();
    ResourceImage::factory()->count(3)->create(['resource_id' => $resource->id]);

    expect($resource->images)->toHaveCount(3);
});

test('resource has many reservations', function () {
    $resource = Resource::factory()->create();
    $user = User::factory()->approved()->create();

    Reservation::factory()->forResource($resource)->forUser($user)->count(2)->create();

    expect($resource->reservations)->toHaveCount(2);
});

test('resource belongs to creator', function () {
    $admin = User::factory()->admin()->create();
    $resource = Resource::factory()->createdBy($admin)->create();

    expect($resource->creator->id)->toBe($admin->id);
});

/*
|--------------------------------------------------------------------------
| Status Method Tests
|--------------------------------------------------------------------------
*/

test('isActive returns true for active resources', function () {
    $active = Resource::factory()->create(['status' => ResourceStatus::Active]);
    $inactive = Resource::factory()->inactive()->create();

    expect($active->isActive())->toBeTrue()
        ->and($inactive->isActive())->toBeFalse();
});

test('canBeReserved returns true for active resources', function () {
    $active = Resource::factory()->create(['status' => ResourceStatus::Active]);
    $maintenance = Resource::factory()->maintenance()->create();
    $inactive = Resource::factory()->inactive()->create();

    expect($active->canBeReserved())->toBeTrue()
        ->and($maintenance->canBeReserved())->toBeFalse()
        ->and($inactive->canBeReserved())->toBeFalse();
});

test('activate changes status to active', function () {
    $resource = Resource::factory()->inactive()->create();

    $resource->activate();

    expect($resource->fresh()->status)->toBe(ResourceStatus::Active);
});

test('deactivate changes status to inactive', function () {
    $resource = Resource::factory()->create(['status' => ResourceStatus::Active]);

    $resource->deactivate();

    expect($resource->fresh()->status)->toBe(ResourceStatus::Inactive);
});

test('markMaintenance changes status to maintenance', function () {
    $resource = Resource::factory()->create(['status' => ResourceStatus::Active]);

    $resource->markMaintenance();

    expect($resource->fresh()->status)->toBe(ResourceStatus::Maintenance);
});

/*
|--------------------------------------------------------------------------
| Pricing Method Tests
|--------------------------------------------------------------------------
*/

test('calculateCost returns flat fee for flat fee pricing', function () {
    $resource = Resource::factory()->flatFee(50.00)->create();

    expect($resource->calculateCost(10))->toBe(50.00); // Units don't matter
});

test('calculateCost multiplies rate by units for per-unit pricing', function () {
    $resource = Resource::factory()->perMile(0.50)->create();

    expect($resource->calculateCost(100))->toBe(50.00);
});

test('formattedPrice shows flat fee correctly', function () {
    $resource = Resource::factory()->flatFee(25.00)->create();

    expect($resource->formatted_price)->toBe('$25.00 flat');
});

test('formattedPrice shows per-unit pricing correctly', function () {
    $resource = Resource::factory()->perHour(15.00)->create();

    expect($resource->formatted_price)->toContain('$15.00');
});

/*
|--------------------------------------------------------------------------
| Query Scope Tests
|--------------------------------------------------------------------------
*/

test('active scope returns only active resources', function () {
    Resource::factory()->count(2)->create(['status' => ResourceStatus::Active]);
    Resource::factory()->inactive()->create();
    Resource::factory()->maintenance()->create();

    expect(Resource::active()->count())->toBe(2);
});

test('vehicles scope returns only vehicles', function () {
    Resource::factory()->vehicle()->count(2)->create();
    Resource::factory()->equipment()->create();

    expect(Resource::vehicles()->count())->toBe(2);
});

test('equipment scope returns only equipment', function () {
    Resource::factory()->equipment()->count(3)->create();
    Resource::factory()->vehicle()->create();

    expect(Resource::equipment()->count())->toBe(3);
});

test('ofType scope filters by resource type', function () {
    Resource::factory()->vehicle()->create();
    Resource::factory()->equipment()->create();
    Resource::factory()->space()->create();

    expect(Resource::ofType(ResourceType::Vehicle)->count())->toBe(1)
        ->and(Resource::ofType(ResourceType::Equipment)->count())->toBe(1)
        ->and(Resource::ofType(ResourceType::Space)->count())->toBe(1);
});

test('available scope returns active resources', function () {
    Resource::factory()->count(2)->create(['status' => ResourceStatus::Active]);
    Resource::factory()->maintenance()->create();

    expect(Resource::available()->count())->toBe(2);
});

