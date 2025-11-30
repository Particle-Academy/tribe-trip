<?php

/**
 * Tests for admin invoice management.
 *
 * Covers invoice listing, status changes, and invoice operations.
 */

use App\Enums\InvoiceStatus;
use App\Livewire\Admin\InvoiceList;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Notifications\InvoiceNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

/*
|--------------------------------------------------------------------------
| Access Control Tests
|--------------------------------------------------------------------------
*/

test('guests cannot access invoice management', function () {
    $this->get(route('admin.invoices'))->assertRedirect(route('login'));
});

test('non-admin users cannot access invoice management', function () {
    $user = createMember();

    $this->actingAs($user)->get(route('admin.invoices'))->assertStatus(403);
});

test('admin can access invoice management', function () {
    $admin = createAdmin();

    $this->actingAs($admin)->get(route('admin.invoices'))->assertStatus(200);
});

/*
|--------------------------------------------------------------------------
| Invoice List Tests
|--------------------------------------------------------------------------
*/

test('invoice list shows all invoices', function () {
    $admin = createAdmin();
    $user = createMember();

    Invoice::factory()->forUser($user)->create(['invoice_number' => 'INV-2025-0001']);
    Invoice::factory()->forUser($user)->create(['invoice_number' => 'INV-2025-0002']);

    Livewire::actingAs($admin)
        ->test(InvoiceList::class)
        ->assertSee('INV-2025-0001')
        ->assertSee('INV-2025-0002');
});

test('invoice list can filter by status', function () {
    $admin = createAdmin();
    $user = createMember();

    Invoice::factory()->forUser($user)->draft()->create(['invoice_number' => 'INV-DRAFT']);
    Invoice::factory()->forUser($user)->sent()->create(['invoice_number' => 'INV-SENT']);
    Invoice::factory()->forUser($user)->paid()->create(['invoice_number' => 'INV-PAID']);

    Livewire::actingAs($admin)
        ->test(InvoiceList::class)
        ->assertSee('INV-DRAFT')
        ->assertSee('INV-SENT')
        ->assertSee('INV-PAID')
        ->set('status', InvoiceStatus::Sent->value)
        ->assertDontSee('INV-DRAFT')
        ->assertSee('INV-SENT')
        ->assertDontSee('INV-PAID');
});

test('invoice list can search by invoice number', function () {
    $admin = createAdmin();
    $user = createMember();

    Invoice::factory()->forUser($user)->create(['invoice_number' => 'INV-2025-0001']);
    Invoice::factory()->forUser($user)->create(['invoice_number' => 'INV-2025-0099']);

    Livewire::actingAs($admin)
        ->test(InvoiceList::class)
        ->set('search', '0099')
        ->assertDontSee('INV-2025-0001')
        ->assertSee('INV-2025-0099');
});

test('invoice list can search by member name', function () {
    $admin = createAdmin();
    $user1 = User::factory()->approved()->create(['name' => 'John Smith']);
    $user2 = User::factory()->approved()->create(['name' => 'Jane Doe']);

    Invoice::factory()->forUser($user1)->create();
    Invoice::factory()->forUser($user2)->create();

    Livewire::actingAs($admin)
        ->test(InvoiceList::class)
        ->set('search', 'John')
        ->assertSee('John Smith')
        ->assertDontSee('Jane Doe');
});

/*
|--------------------------------------------------------------------------
| Invoice Status Management Tests
|--------------------------------------------------------------------------
*/

test('admin can send draft invoice', function () {
    Notification::fake();

    $admin = createAdmin();
    $user = createMember();

    $invoice = Invoice::factory()->forUser($user)->draft()->create();
    // Create at least one item so invoice can be sent
    InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

    Livewire::actingAs($admin)
        ->test(InvoiceList::class)
        ->call('sendInvoice', $invoice->id);

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::Sent)
        ->and($invoice->sent_at)->not->toBeNull();

    Notification::assertSentTo($user, InvoiceNotification::class);
});

test('admin can mark invoice as paid', function () {
    $admin = createAdmin();
    $user = createMember();

    $invoice = Invoice::factory()->forUser($user)->sent()->create();

    Livewire::actingAs($admin)
        ->test(InvoiceList::class)
        ->call('openMarkPaidModal', $invoice->id)
        ->call('markAsPaid');

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::Paid)
        ->and($invoice->paid_at)->not->toBeNull();
});

test('admin can void invoice', function () {
    $admin = createAdmin();
    $user = createMember();

    $invoice = Invoice::factory()->forUser($user)->draft()->create();

    Livewire::actingAs($admin)
        ->test(InvoiceList::class)
        ->call('voidInvoice', $invoice->id);

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::Voided);
});

test('cannot send invoice without items', function () {
    $admin = createAdmin();
    $user = createMember();

    $invoice = Invoice::factory()->forUser($user)->draft()->create();
    // No items created

    Livewire::actingAs($admin)
        ->test(InvoiceList::class)
        ->call('sendInvoice', $invoice->id);

    $invoice->refresh();
    // Invoice should still be draft since it has no items
    expect($invoice->status)->toBe(InvoiceStatus::Draft);
});

test('cannot mark draft invoice as paid', function () {
    $admin = createAdmin();
    $user = createMember();

    $invoice = Invoice::factory()->forUser($user)->draft()->create();

    Livewire::actingAs($admin)
        ->test(InvoiceList::class)
        ->call('openMarkPaidModal', $invoice->id)
        ->call('markAsPaid');

    $invoice->refresh();
    // Invoice should still be draft
    expect($invoice->status)->toBe(InvoiceStatus::Draft);
});

