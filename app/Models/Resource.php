<?php

namespace App\Models;

use App\Enums\PricingModel;
use App\Enums\PricingUnit;
use App\Enums\ResourceStatus;
use App\Enums\ResourceType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Resource model for community-shared resources.
 *
 * Represents vehicles, equipment, spaces, etc. that can be reserved.
 */
class Resource extends Model
{
    /** @use HasFactory<\Database\Factories\ResourceFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'type',
        'status',
        'pricing_model',
        'rate',
        'pricing_unit',
        'requires_approval',
        'max_reservation_days',
        'advance_booking_days',
        'created_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ResourceType::class,
            'status' => ResourceStatus::class,
            'pricing_model' => PricingModel::class,
            'pricing_unit' => PricingUnit::class,
            'rate' => 'decimal:2',
            'requires_approval' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the images for this resource.
     */
    public function images(): HasMany
    {
        return $this->hasMany(ResourceImage::class)->orderBy('order');
    }

    /**
     * Get the user who created this resource.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the reservations for this resource.
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Get the primary image URL.
     */
    public function getPrimaryImageUrlAttribute(): ?string
    {
        $primaryImage = $this->images->firstWhere('is_primary', true)
            ?? $this->images->first();

        return $primaryImage ? asset('storage/' . $primaryImage->path) : null;
    }

    /**
     * Get a formatted price string for display.
     */
    public function getFormattedPriceAttribute(): string
    {
        $price = '$' . number_format($this->rate, 2);

        if ($this->pricing_model === PricingModel::PerUnit && $this->pricing_unit) {
            return $price . '/' . $this->pricing_unit->abbreviation();
        }

        return $price . ' flat';
    }

    /*
    |--------------------------------------------------------------------------
    | Status Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if the resource is active.
     */
    public function isActive(): bool
    {
        return $this->status === ResourceStatus::Active;
    }

    /**
     * Check if the resource can be reserved.
     */
    public function canBeReserved(): bool
    {
        return $this->status->canBeReserved();
    }

    /**
     * Activate the resource.
     */
    public function activate(): bool
    {
        return $this->update(['status' => ResourceStatus::Active]);
    }

    /**
     * Deactivate the resource.
     */
    public function deactivate(): bool
    {
        return $this->update(['status' => ResourceStatus::Inactive]);
    }

    /**
     * Mark resource as under maintenance.
     */
    public function markMaintenance(): bool
    {
        return $this->update(['status' => ResourceStatus::Maintenance]);
    }

    /*
    |--------------------------------------------------------------------------
    | Booking Duration Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if resource allows multi-day bookings.
     *
     * Returns true if max_reservation_days is null (unlimited) or > 0.
     * Returns false if max_reservation_days is 0 (single day only).
     */
    public function allowsMultiDayBooking(): bool
    {
        return $this->max_reservation_days === null || $this->max_reservation_days > 0;
    }

    /**
     * Get the maximum booking duration in days.
     *
     * Returns null for unlimited, 0 for single day only, or the max days.
     */
    public function maxBookingDays(): ?int
    {
        return $this->max_reservation_days;
    }

    /**
     * Check if a booking duration is valid for this resource.
     *
     * @param  int  $days  Number of days for the booking
     */
    public function isValidBookingDuration(int $days): bool
    {
        // Single day only (max_reservation_days = 0)
        if ($this->max_reservation_days === 0) {
            return $days <= 1;
        }

        // Unlimited (max_reservation_days = null)
        if ($this->max_reservation_days === null) {
            return true;
        }

        // Max days limit set
        return $days <= $this->max_reservation_days;
    }

    /*
    |--------------------------------------------------------------------------
    | Pricing Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Calculate the cost for a given usage.
     *
     * @param  float  $units  Number of units (hours, miles, etc.)
     */
    public function calculateCost(float $units = 1): float
    {
        if ($this->pricing_model === PricingModel::FlatFee) {
            return (float) $this->rate;
        }

        return (float) $this->rate * $units;
    }

    /**
     * Calculate cost for a reservation based on start and end times.
     *
     * For flat fee: returns the flat rate (per reservation).
     * For per-unit: calculates based on the pricing unit (hour, day, etc.).
     *
     * @param  \Carbon\Carbon  $startsAt  Reservation start time
     * @param  \Carbon\Carbon  $endsAt  Reservation end time
     */
    public function calculateReservationCost(\Carbon\Carbon $startsAt, \Carbon\Carbon $endsAt): float
    {
        if ($this->pricing_model === PricingModel::FlatFee) {
            return (float) $this->rate;
        }

        // For per-unit pricing, calculate based on the pricing unit
        $units = match ($this->pricing_unit) {
            PricingUnit::Hour => $startsAt->diffInMinutes($endsAt) / 60,
            PricingUnit::Day => $startsAt->copy()->startOfDay()->diffInDays($endsAt->copy()->startOfDay()) + 1,
            // Mile and Kilometer are tracked via usage log, not time-based
            // Trip is per-trip, equivalent to flat fee for reservation purposes
            default => 1,
        };

        return (float) $this->rate * $units;
    }

    /**
     * Get a formatted cost estimate string for a reservation.
     *
     * @param  \Carbon\Carbon  $startsAt  Reservation start time
     * @param  \Carbon\Carbon  $endsAt  Reservation end time
     */
    public function getReservationCostEstimate(\Carbon\Carbon $startsAt, \Carbon\Carbon $endsAt): string
    {
        $cost = $this->calculateReservationCost($startsAt, $endsAt);

        if ($this->pricing_model === PricingModel::FlatFee) {
            return '$' . number_format($cost, 2) . ' flat';
        }

        $units = match ($this->pricing_unit) {
            PricingUnit::Hour => round($startsAt->diffInMinutes($endsAt) / 60, 1) . ' ' . ($startsAt->diffInMinutes($endsAt) / 60 == 1 ? 'hour' : 'hours'),
            PricingUnit::Day => ($startsAt->copy()->startOfDay()->diffInDays($endsAt->copy()->startOfDay()) + 1) . ' ' . ($startsAt->copy()->startOfDay()->diffInDays($endsAt->copy()->startOfDay()) + 1 == 1 ? 'day' : 'days'),
            default => '',
        };

        return '$' . number_format($cost, 2) . ($units ? " ({$units})" : '');
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to filter by active status.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ResourceStatus::Active);
    }

    /**
     * Scope to filter by resource type.
     */
    public function scopeOfType(Builder $query, ResourceType $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter by vehicles.
     */
    public function scopeVehicles(Builder $query): Builder
    {
        return $query->where('type', ResourceType::Vehicle);
    }

    /**
     * Scope to filter by equipment.
     */
    public function scopeEquipment(Builder $query): Builder
    {
        return $query->where('type', ResourceType::Equipment);
    }

    /**
     * Scope to filter by available (active) resources.
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', ResourceStatus::Active);
    }
}
