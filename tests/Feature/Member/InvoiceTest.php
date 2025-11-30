<?php

/**
 * Tests for member invoice viewing.
 *
 * Covers invoice listing and viewing for members.
 */

use App\Enums\InvoiceStatus;
use App\Livewire\Member\MyInvoices;
use App\Models\Invoice;
use App\Models\User;
use Livewire\Livewire;

/*
|--------------------------------------------------------------------------
| Access Control Tests
|--------------------------------------------------------------------------
*/

test('guests cannot access invoices page', function () {
    $this->get(route('member.invoices'))->assertRedirect(route('login'));
});

test('pending users cannot access invoices page', function () {
    $user = User::factory()->pending()->create();

    // Pending users are redirected to the pending approval page
    $this->actingAs($user)
        ->get(route('member.invoices'))
        ->assertRedirect(route('register.pending'));
});

test('approved members can access invoices page', function () {
    $member = createMember();

    $this->actingAs($member)
        ->get(route('member.invoices'))
        ->assertStatus(200);
});

/*
|--------------------------------------------------------------------------
| Invoice Listing Tests
|--------------------------------------------------------------------------
*/

test('member can see their invoices', function () {
    $member = createMember();

    Invoice::factory()->forUser($member)->sent()->create(['invoice_number' => 'INV-2025-0001']);

    Livewire::actingAs($member)
        ->test(MyInvoices::class)
        ->assertSee('INV-2025-0001');
});

test('member cannot see other users invoices', function () {
    $member = createMember();
    $otherMember = createMember();

    Invoice::factory()->forUser($otherMember)->sent()->create(['invoice_number' => 'INV-OTHER']);

    Livewire::actingAs($member)
        ->test(MyInvoices::class)
        ->assertDontSee('INV-OTHER');
});

test('member cannot see draft invoices', function () {
    $member = createMember();

    Invoice::factory()->forUser($member)->draft()->create(['invoice_number' => 'INV-DRAFT']);

    Livewire::actingAs($member)
        ->test(MyInvoices::class)
        ->assertDontSee('INV-DRAFT');
});

test('invoice list shows sent and paid invoices', function () {
    $member = createMember();

    Invoice::factory()->forUser($member)->sent()->create(['invoice_number' => 'INV-SENT']);
    Invoice::factory()->forUser($member)->paid()->create(['invoice_number' => 'INV-PAID']);

    Livewire::actingAs($member)
        ->test(MyInvoices::class)
        ->assertSee('INV-SENT')
        ->assertSee('INV-PAID');
});

test('invoice list can filter by status', function () {
    $member = createMember();

    Invoice::factory()->forUser($member)->sent()->create(['invoice_number' => 'INV-OUTSTANDING']);
    Invoice::factory()->forUser($member)->paid()->create(['invoice_number' => 'INV-CLEARED']);

    // MyInvoices uses 'filter' property (not 'status')
    Livewire::actingAs($member)
        ->test(MyInvoices::class)
        ->set('filter', 'outstanding')
        ->assertSee('INV-OUTSTANDING')
        ->assertDontSee('INV-CLEARED');
});

test('invoice list shows total amount', function () {
    $member = createMember();

    Invoice::factory()->forUser($member)->sent()->create([
        'total' => 125.50,
    ]);

    Livewire::actingAs($member)
        ->test(MyInvoices::class)
        ->assertSee('$125.50');
});

test('invoice list shows status badge', function () {
    $member = createMember();

    Invoice::factory()->forUser($member)->overdue()->create();

    Livewire::actingAs($member)
        ->test(MyInvoices::class)
        ->assertSee('Overdue');
});

/*
|--------------------------------------------------------------------------
| Invoice Download Tests
|--------------------------------------------------------------------------
*/

test('member can download their invoice', function () {
    $member = createMember();

    $invoice = Invoice::factory()->forUser($member)->sent()->create();

    $this->actingAs($member)
        ->get(route('member.invoices.download', $invoice))
        ->assertStatus(200);
});

test('member cannot download other users invoice', function () {
    $member = createMember();
    $otherMember = createMember();

    $invoice = Invoice::factory()->forUser($otherMember)->sent()->create();

    $this->actingAs($member)
        ->get(route('member.invoices.download', $invoice))
        ->assertStatus(403);
});

test('member cannot download draft invoice', function () {
    $member = createMember();

    $invoice = Invoice::factory()->forUser($member)->draft()->create();

    // Draft invoices are not accessible to members (returns 404)
    $this->actingAs($member)
        ->get(route('member.invoices.download', $invoice))
        ->assertStatus(404);
});

