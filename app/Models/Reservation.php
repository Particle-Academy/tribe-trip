<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Reservation model for resource bookings.
 *
 * Tracks member reservations with time slots, status, and lifecycle.
 */
class Reservation extends Model
{
    /** @use HasFactory<\Database\Factories\ReservationFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'resource_id',
        'user_id',
        'starts_at',
        'ends_at',
        'status',
        'notes',
        'admin_notes',
        'cancelled_at',
        'cancellation_reason',
        'cancelled_by',
        'confirmed_at',
        'confirmed_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => ReservationStatus::class,
            'cancelled_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the resource being reserved.
     */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    /**
     * Get the member who made the reservation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who cancelled the reservation.
     */
    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * Get the admin who confirmed the reservation.
     */
    public function confirmedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * Get the usage log for this reservation.
     */
    public function usageLog(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(UsageLog::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Get the duration of the reservation in hours.
     */
    public function getDurationHoursAttribute(): float
    {
        return $this->starts_at->diffInMinutes($this->ends_at) / 60;
    }

    /**
     * Get a formatted duration string.
     */
    public function getFormattedDurationAttribute(): string
    {
        $hours = $this->duration_hours;

        if ($hours < 1) {
            return round($hours * 60) . ' min';
        }

        if ($hours == (int) $hours) {
            return (int) $hours . ' hr' . ($hours > 1 ? 's' : '');
        }

        return number_format($hours, 1) . ' hrs';
    }

    /**
     * Check if the reservation is in the past.
     */
    public function getIsPastAttribute(): bool
    {
        return $this->ends_at->isPast();
    }

    /**
     * Check if the reservation is currently active (in progress).
     */
    public function getIsInProgressAttribute(): bool
    {
        return $this->starts_at->isPast() && $this->ends_at->isFuture();
    }

    /**
     * Check if the reservation is upcoming.
     */
    public function getIsUpcomingAttribute(): bool
    {
        return $this->starts_at->isFuture();
    }

    /*
    |--------------------------------------------------------------------------
    | Status Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if the reservation is pending.
     */
    public function isPending(): bool
    {
        return $this->status === ReservationStatus::Pending;
    }

    /**
     * Check if the reservation is confirmed.
     */
    public function isConfirmed(): bool
    {
        return $this->status === ReservationStatus::Confirmed;
    }

    /**
     * Check if the reservation is checked out.
     */
    public function isCheckedOut(): bool
    {
        return $this->status === ReservationStatus::CheckedOut;
    }

    /**
     * Check if the reservation is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === ReservationStatus::Completed;
    }

    /**
     * Check if the reservation is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === ReservationStatus::Cancelled;
    }

    /**
     * Check if the reservation can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return $this->status->canBeCancelled() && $this->starts_at->isFuture();
    }

    /**
     * Check if the reservation can be confirmed.
     */
    public function canBeConfirmed(): bool
    {
        return $this->isPending();
    }

    /**
     * Confirm the reservation.
     */
    public function confirm(?int $confirmedBy = null): bool
    {
        if (! $this->canBeConfirmed()) {
            return false;
        }

        return $this->update([
            'status' => ReservationStatus::Confirmed,
            'confirmed_at' => now(),
            'confirmed_by' => $confirmedBy ?? auth()->id(),
        ]);
    }

    /**
     * Cancel the reservation.
     */
    public function cancel(?string $reason = null, ?int $cancelledBy = null): bool
    {
        if (! $this->canBeCancelled()) {
            return false;
        }

        return $this->update([
            'status' => ReservationStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'cancelled_by' => $cancelledBy ?? auth()->id(),
        ]);
    }

    /**
     * Mark as checked out.
     */
    public function checkOut(): bool
    {
        if (! $this->isConfirmed()) {
            return false;
        }

        return $this->update([
            'status' => ReservationStatus::CheckedOut,
        ]);
    }

    /**
     * Mark as completed.
     */
    public function complete(): bool
    {
        if (! $this->isCheckedOut()) {
            return false;
        }

        return $this->update([
            'status' => ReservationStatus::Completed,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to filter by upcoming reservations.
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>', now())
            ->whereIn('status', ReservationStatus::upcomingStatuses());
    }

    /**
     * Scope to filter by past reservations.
     */
    public function scopePast(Builder $query): Builder
    {
        return $query->where('ends_at', '<', now());
    }

    /**
     * Scope to filter by current (in-progress) reservations.
     */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->whereIn('status', [ReservationStatus::Confirmed, ReservationStatus::CheckedOut]);
    }

    /**
     * Scope to filter reservations that block availability.
     */
    public function scopeBlocking(Builder $query): Builder
    {
        return $query->whereIn('status', ReservationStatus::blockingStatuses());
    }

    /**
     * Scope to filter by resource.
     */
    public function scopeForResource(Builder $query, int $resourceId): Builder
    {
        return $query->where('resource_id', $resourceId);
    }

    /**
     * Scope to filter by user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeWithStatus(Builder $query, ReservationStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by date range overlap.
     */
    public function scopeOverlapping(Builder $query, $startsAt, $endsAt): Builder
    {
        return $query->where(function ($q) use ($startsAt, $endsAt) {
            $q->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt);
        });
    }

    /**
     * Scope for a specific date.
     */
    public function scopeOnDate(Builder $query, $date): Builder
    {
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        return $query->where(function ($q) use ($startOfDay, $endOfDay) {
            $q->whereBetween('starts_at', [$startOfDay, $endOfDay])
                ->orWhereBetween('ends_at', [$startOfDay, $endOfDay])
                ->orWhere(function ($q) use ($startOfDay, $endOfDay) {
                    $q->where('starts_at', '<=', $startOfDay)
                        ->where('ends_at', '>=', $endOfDay);
                });
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Static Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if a time slot is available for a resource.
     */
    public static function isSlotAvailable(int $resourceId, $startsAt, $endsAt, ?int $excludeId = null): bool
    {
        $query = self::query()
            ->forResource($resourceId)
            ->blocking()
            ->overlapping($startsAt, $endsAt);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return ! $query->exists();
    }

    /**
     * Get blocking reservations for a resource within a date range.
     */
    public static function getBlockingForResource(int $resourceId, $startDate, $endDate): \Illuminate\Support\Collection
    {
        return self::query()
            ->forResource($resourceId)
            ->blocking()
            ->where('starts_at', '<', $endDate)
            ->where('ends_at', '>', $startDate)
            ->orderBy('starts_at')
            ->get();
    }
}
