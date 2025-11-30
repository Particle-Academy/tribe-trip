<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * InvoiceItem model for individual line items on an invoice.
 *
 * Each item represents a usage charge or manual entry.
 */
class InvoiceItem extends Model
{
    /** @use HasFactory<\Database\Factories\InvoiceItemFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'invoice_id',
        'usage_log_id',
        'resource_id',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'amount',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the invoice this item belongs to.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the usage log this item was generated from.
     */
    public function usageLog(): BelongsTo
    {
        return $this->belongsTo(UsageLog::class);
    }

    /**
     * Get the resource this item is for.
     */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Get formatted unit price.
     */
    public function getFormattedUnitPriceAttribute(): string
    {
        return '$' . number_format($this->unit_price, 2);
    }

    /**
     * Get formatted amount.
     */
    public function getFormattedAmountAttribute(): string
    {
        return '$' . number_format($this->amount, 2);
    }

    /**
     * Get formatted quantity with unit.
     */
    public function getFormattedQuantityAttribute(): string
    {
        $qty = number_format($this->quantity, $this->quantity == (int) $this->quantity ? 0 : 2);

        if ($this->unit) {
            return "{$qty} {$this->unit}";
        }

        return $qty;
    }

    /*
    |--------------------------------------------------------------------------
    | Factory Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Create an invoice item from a usage log.
     *
     * Extracts usage details and pricing to populate the line item.
     */
    public static function createFromUsageLog(UsageLog $usageLog): self
    {
        $resource = $usageLog->resource;

        // Determine the description based on resource and usage
        $description = $resource->name;
        if ($usageLog->checked_out_at && $usageLog->checked_in_at) {
            $description .= ' - ' . $usageLog->checked_out_at->format('M j, Y');
        }

        // Determine quantity and unit based on pricing model
        $quantity = 1;
        $unit = null;
        $unitPrice = (float) $resource->rate;

        if ($resource->pricing_unit) {
            $unit = $resource->pricing_unit->abbreviation();

            // Use distance for per-unit (miles, km) or duration for time-based
            if (in_array($resource->pricing_unit->value, ['mile', 'kilometer'])) {
                $quantity = (float) $usageLog->distance_units ?: 1;
            } elseif ($resource->pricing_unit->value === 'hour') {
                $quantity = (float) $usageLog->duration_hours ?: 1;
            } elseif ($resource->pricing_unit->value === 'day') {
                $quantity = $usageLog->duration_hours ? ceil($usageLog->duration_hours / 24) : 1;
            }
        }

        $amount = (float) $usageLog->calculated_cost ?: ($quantity * $unitPrice);

        return new self([
            'usage_log_id' => $usageLog->id,
            'resource_id' => $resource->id,
            'description' => $description,
            'quantity' => $quantity,
            'unit' => $unit,
            'unit_price' => $unitPrice,
            'amount' => $amount,
        ]);
    }
}
