<?php

/**
 * Unit tests for the Invoice model.
 *
 * Tests relationships, status methods, calculations, and scopes.
 */

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Relationship Tests
|--------------------------------------------------------------------------
*/

test('invoice belongs to user', function () {
    $user = User::factory()->approved()->create();
    $invoice = Invoice::factory()->forUser($user)->create();

    expect($invoice->user->id)->toBe($user->id);
});

test('invoice belongs to generator', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->approved()->create();
    $invoice = Invoice::factory()->forUser($user)->generatedBy($admin)->create();

    expect($invoice->generator->id)->toBe($admin->id);
});

test('invoice has many items', function () {
    $user = User::factory()->approved()->create();
    $invoice = Invoice::factory()->forUser($user)->create();
    InvoiceItem::factory()->count(3)->create(['invoice_id' => $invoice->id]);

    expect($invoice->items)->toHaveCount(3);
});

/*
|--------------------------------------------------------------------------
| Invoice Number Generation Tests
|--------------------------------------------------------------------------
*/

test('generateInvoiceNumber creates sequential numbers', function () {
    $user = User::factory()->approved()->create();
    $year = now()->year;

    $number1 = Invoice::generateInvoiceNumber();
    Invoice::factory()->forUser($user)->create(['invoice_number' => $number1]);

    $number2 = Invoice::generateInvoiceNumber();

    expect($number1)->toBe("INV-{$year}-0001")
        ->and($number2)->toBe("INV-{$year}-0002");
});

/*
|--------------------------------------------------------------------------
| Status Method Tests
|--------------------------------------------------------------------------
*/

test('isEditable returns true for draft invoices', function () {
    $invoice = Invoice::factory()->draft()->create();

    expect($invoice->isEditable())->toBeTrue();
});

test('isEditable returns false for sent invoices', function () {
    $invoice = Invoice::factory()->sent()->create();

    expect($invoice->isEditable())->toBeFalse();
});

test('canBeSent returns true for draft invoice with items', function () {
    $invoice = Invoice::factory()->draft()->create();
    InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

    expect($invoice->canBeSent())->toBeTrue();
});

test('canBeSent returns false for draft invoice without items', function () {
    $invoice = Invoice::factory()->draft()->create();

    expect($invoice->canBeSent())->toBeFalse();
});

test('canBeMarkedPaid returns true for sent invoices', function () {
    $invoice = Invoice::factory()->sent()->create();

    expect($invoice->canBeMarkedPaid())->toBeTrue();
});

test('canBeMarkedPaid returns false for draft invoices', function () {
    $invoice = Invoice::factory()->draft()->create();

    expect($invoice->canBeMarkedPaid())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Status Change Method Tests
|--------------------------------------------------------------------------
*/

test('markAsSent changes status and sets sent_at', function () {
    $invoice = Invoice::factory()->draft()->create();
    InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

    $invoice->markAsSent(notify: false);

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::Sent)
        ->and($invoice->sent_at)->not->toBeNull();
});

test('markAsPaid changes status and sets paid_at', function () {
    $invoice = Invoice::factory()->sent()->create();

    $invoice->markAsPaid();

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::Paid)
        ->and($invoice->paid_at)->not->toBeNull();
});

test('void changes status to voided', function () {
    $invoice = Invoice::factory()->draft()->create();

    $invoice->void();

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Voided);
});

test('markAsOverdue changes sent invoice to overdue', function () {
    $invoice = Invoice::factory()->sent()->create();

    $invoice->markAsOverdue(notify: false);

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Overdue);
});

/*
|--------------------------------------------------------------------------
| Calculation Method Tests
|--------------------------------------------------------------------------
*/

test('recalculateTotals sums items correctly', function () {
    $invoice = Invoice::factory()->draft()->create(['subtotal' => 0, 'total' => 0, 'adjustments' => 0]);
    InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'amount' => 50.00]);
    InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'amount' => 75.00]);

    $invoice->recalculateTotals();

    $invoice->refresh();
    expect((float) $invoice->subtotal)->toBe(125.00)
        ->and((float) $invoice->total)->toBe(125.00);
});

test('applyAdjustment updates total correctly with discount', function () {
    $invoice = Invoice::factory()->draft()->create(['subtotal' => 100.00, 'total' => 100.00]);

    $invoice->applyAdjustment(-10.00, 'Member discount');

    $invoice->refresh();
    expect((float) $invoice->adjustments)->toBe(-10.00)
        ->and((float) $invoice->total)->toBe(90.00)
        ->and($invoice->adjustment_reason)->toBe('Member discount');
});

test('applyAdjustment updates total correctly with late fee', function () {
    $invoice = Invoice::factory()->draft()->create(['subtotal' => 100.00, 'total' => 100.00]);

    $invoice->applyAdjustment(15.00, 'Late fee');

    $invoice->refresh();
    expect((float) $invoice->adjustments)->toBe(15.00)
        ->and((float) $invoice->total)->toBe(115.00);
});

/*
|--------------------------------------------------------------------------
| Formatted Attribute Tests
|--------------------------------------------------------------------------
*/

test('formattedSubtotal formats correctly', function () {
    $invoice = Invoice::factory()->create(['subtotal' => 125.50]);

    expect($invoice->formatted_subtotal)->toBe('$125.50');
});

test('formattedTotal formats correctly', function () {
    $invoice = Invoice::factory()->create(['total' => 250.00]);

    expect($invoice->formatted_total)->toBe('$250.00');
});

test('formattedAdjustments shows discount with prefix', function () {
    $invoice = Invoice::factory()->withDiscount(10.00)->create();

    expect($invoice->formatted_adjustments)->toContain('-$10.00');
});

/*
|--------------------------------------------------------------------------
| Query Scope Tests
|--------------------------------------------------------------------------
*/

test('draft scope returns only draft invoices', function () {
    Invoice::factory()->draft()->count(2)->create();
    Invoice::factory()->sent()->create();

    expect(Invoice::draft()->count())->toBe(2);
});

test('sent scope returns only sent invoices', function () {
    Invoice::factory()->sent()->count(3)->create();
    Invoice::factory()->draft()->create();

    expect(Invoice::sent()->count())->toBe(3);
});

test('paid scope returns only paid invoices', function () {
    Invoice::factory()->paid()->count(2)->create();
    Invoice::factory()->sent()->create();

    expect(Invoice::paid()->count())->toBe(2);
});

test('outstanding scope returns sent and overdue invoices', function () {
    Invoice::factory()->sent()->count(2)->create();
    Invoice::factory()->overdue()->create();
    Invoice::factory()->paid()->create();

    expect(Invoice::outstanding()->count())->toBe(3);
});

test('forUser scope filters by user', function () {
    $user1 = User::factory()->approved()->create();
    $user2 = User::factory()->approved()->create();

    Invoice::factory()->forUser($user1)->count(3)->create();
    Invoice::factory()->forUser($user2)->create();

    expect(Invoice::forUser($user1->id)->count())->toBe(3);
});

