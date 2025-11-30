<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Notifications\InvoiceNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Invoice model for billing members based on resource usage.
 *
 * Aggregates usage charges for a billing period into a single invoice.
 */
class Invoice extends Model
{
    /** @use HasFactory<\Database\Factories\InvoiceFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'invoice_number',
        'billing_period_start',
        'billing_period_end',
        'subtotal',
        'adjustments',
        'adjustment_reason',
        'total',
        'status',
        'due_date',
        'sent_at',
        'paid_at',
        'notes',
        'generated_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'billing_period_start' => 'date',
            'billing_period_end' => 'date',
            'subtotal' => 'decimal:2',
            'adjustments' => 'decimal:2',
            'total' => 'decimal:2',
            'status' => InvoiceStatus::class,
            'due_date' => 'date',
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the member this invoice belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who generated this invoice.
     */
    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Get the line items for this invoice.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Get formatted subtotal.
     */
    public function getFormattedSubtotalAttribute(): string
    {
        return '$' . number_format($this->subtotal, 2);
    }

    /**
     * Get formatted adjustments (shows + or - prefix before $).
     */
    public function getFormattedAdjustmentsAttribute(): string
    {
        if ($this->adjustments == 0) {
            return '$0.00';
        }

        if ($this->adjustments > 0) {
            return '+$'.number_format($this->adjustments, 2);
        }

        // Negative adjustment: show -$10.00 format
        return '-$'.number_format(abs($this->adjustments), 2);
    }

    /**
     * Get formatted total.
     */
    public function getFormattedTotalAttribute(): string
    {
        return '$' . number_format($this->total, 2);
    }

    /**
     * Get billing period as formatted string.
     */
    public function getBillingPeriodAttribute(): string
    {
        return $this->billing_period_start->format('M j') . ' - ' . $this->billing_period_end->format('M j, Y');
    }

    /*
    |--------------------------------------------------------------------------
    | Invoice Number Generation
    |--------------------------------------------------------------------------
    */

    /**
     * Generate a unique invoice number.
     *
     * Format: INV-YYYY-XXXX where XXXX is zero-padded sequential number
     */
    public static function generateInvoiceNumber(): string
    {
        $year = now()->year;
        $prefix = "INV-{$year}-";

        $lastInvoice = static::query()
            ->where('invoice_number', 'like', "{$prefix}%")
            ->orderByDesc('invoice_number')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /*
    |--------------------------------------------------------------------------
    | Status Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if the invoice is editable.
     */
    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }

    /**
     * Check if the invoice can be sent.
     */
    public function canBeSent(): bool
    {
        return $this->status->canBeSent() && $this->items()->exists();
    }

    /**
     * Check if the invoice can be marked as paid.
     */
    public function canBeMarkedPaid(): bool
    {
        return $this->status->canBeMarkedPaid();
    }

    /**
     * Check if the invoice can be voided.
     */
    public function canBeVoided(): bool
    {
        return $this->status->canBeVoided();
    }

    /**
     * Mark the invoice as sent and notify the user.
     */
    public function markAsSent(bool $notify = true): bool
    {
        if (! $this->canBeSent()) {
            return false;
        }

        $updated = $this->update([
            'status' => InvoiceStatus::Sent,
            'sent_at' => now(),
        ]);

        if ($updated && $notify) {
            $this->sendNotification('sent');
        }

        return $updated;
    }

    /**
     * Send an invoice notification to the user.
     */
    public function sendNotification(string $type = 'sent'): void
    {
        $this->user->notify(new InvoiceNotification($this, $type));
    }

    /**
     * Mark the invoice as paid.
     */
    public function markAsPaid(): bool
    {
        if (! $this->canBeMarkedPaid()) {
            return false;
        }

        return $this->update([
            'status' => InvoiceStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    /**
     * Void the invoice.
     */
    public function void(): bool
    {
        if (! $this->canBeVoided()) {
            return false;
        }

        return $this->update([
            'status' => InvoiceStatus::Voided,
        ]);
    }

    /**
     * Mark the invoice as overdue and optionally notify the user.
     */
    public function markAsOverdue(bool $notify = true): bool
    {
        if ($this->status !== InvoiceStatus::Sent) {
            return false;
        }

        $updated = $this->update([
            'status' => InvoiceStatus::Overdue,
        ]);

        if ($updated && $notify) {
            $this->sendNotification('overdue');
        }

        return $updated;
    }

    /*
    |--------------------------------------------------------------------------
    | Calculation Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Recalculate subtotal from items and update total.
     */
    public function recalculateTotals(): bool
    {
        $subtotal = $this->items()->sum('amount');
        $total = $subtotal + $this->adjustments;

        return $this->update([
            'subtotal' => $subtotal,
            'total' => $total,
        ]);
    }

    /**
     * Apply an adjustment to the invoice.
     */
    public function applyAdjustment(float $amount, ?string $reason = null): bool
    {
        if (! $this->isEditable()) {
            return false;
        }

        $total = $this->subtotal + $amount;

        return $this->update([
            'adjustments' => $amount,
            'adjustment_reason' => $reason,
            'total' => $total,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to filter by draft status.
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', InvoiceStatus::Draft);
    }

    /**
     * Scope to filter by sent status.
     */
    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', InvoiceStatus::Sent);
    }

    /**
     * Scope to filter by paid status.
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', InvoiceStatus::Paid);
    }

    /**
     * Scope to filter by overdue status.
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', InvoiceStatus::Overdue);
    }

    /**
     * Scope to filter by outstanding (unpaid) invoices.
     */
    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->whereIn('status', [InvoiceStatus::Sent, InvoiceStatus::Overdue]);
    }

    /**
     * Scope to filter by user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by billing period.
     */
    public function scopeForBillingPeriod(Builder $query, $start, $end): Builder
    {
        return $query->where('billing_period_start', $start)
            ->where('billing_period_end', $end);
    }

    /**
     * Scope to filter invoices that are past due date.
     */
    public function scopePastDue(Builder $query): Builder
    {
        return $query->where('status', InvoiceStatus::Sent)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now());
    }
}
