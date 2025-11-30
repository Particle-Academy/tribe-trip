<?php

namespace App\Models;

use App\Enums\UsageLogStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * UsageLog model for tracking resource usage.
 *
 * Records check-out/check-in with readings, photos, and calculated usage.
 */
class UsageLog extends Model
{
    /** @use HasFactory<\Database\Factories\UsageLogFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'reservation_id',
        'user_id',
        'resource_id',
        'status',
        'checked_out_at',
        'start_reading',
        'start_photo_path',
        'start_notes',
        'checked_in_at',
        'end_reading',
        'end_photo_path',
        'end_notes',
        'duration_hours',
        'distance_units',
        'calculated_cost',
        'verified_by',
        'verified_at',
        'admin_notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => UsageLogStatus::class,
            'checked_out_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'verified_at' => 'datetime',
            'start_reading' => 'decimal:2',
            'end_reading' => 'decimal:2',
            'duration_hours' => 'decimal:2',
            'distance_units' => 'decimal:2',
            'calculated_cost' => 'decimal:2',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the reservation this usage is for.
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * Get the user who used the resource.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the resource that was used.
     */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    /**
     * Get the admin who verified this usage.
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Get the invoice item for this usage log.
     */
    public function invoiceItem(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(InvoiceItem::class);
    }

    /**
     * Check if this usage has been invoiced.
     */
    public function isInvoiced(): bool
    {
        return $this->invoiceItem()->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Get the start photo URL.
     */
    public function getStartPhotoUrlAttribute(): ?string
    {
        return $this->start_photo_path
            ? asset('storage/' . $this->start_photo_path)
            : null;
    }

    /**
     * Get the end photo URL.
     */
    public function getEndPhotoUrlAttribute(): ?string
    {
        return $this->end_photo_path
            ? asset('storage/' . $this->end_photo_path)
            : null;
    }

    /**
     * Get formatted duration.
     */
    public function getFormattedDurationAttribute(): ?string
    {
        if (! $this->duration_hours) {
            return null;
        }

        $hours = floor($this->duration_hours);
        $minutes = round(($this->duration_hours - $hours) * 60);

        if ($hours > 0 && $minutes > 0) {
            return "{$hours}h {$minutes}m";
        } elseif ($hours > 0) {
            return "{$hours}h";
        } else {
            return "{$minutes}m";
        }
    }

    /**
     * Get formatted distance with unit.
     */
    public function getFormattedDistanceAttribute(): ?string
    {
        if (! $this->distance_units) {
            return null;
        }

        $unit = $this->resource?->pricing_unit?->abbreviation() ?? 'units';

        return number_format($this->distance_units, 1) . ' ' . $unit;
    }

    /**
     * Get formatted cost.
     */
    public function getFormattedCostAttribute(): ?string
    {
        if ($this->calculated_cost === null) {
            return null;
        }

        return '$' . number_format($this->calculated_cost, 2);
    }

    /*
    |--------------------------------------------------------------------------
    | Status Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if usage is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === UsageLogStatus::CheckedOut;
    }

    /**
     * Check if usage is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === UsageLogStatus::Completed;
    }

    /**
     * Check if usage is verified.
     */
    public function isVerified(): bool
    {
        return $this->status === UsageLogStatus::Verified;
    }

    /**
     * Check if usage is disputed.
     */
    public function isDisputed(): bool
    {
        return $this->status === UsageLogStatus::Disputed;
    }

    /**
     * Check if usage can be checked in.
     */
    public function canCheckIn(): bool
    {
        return $this->isInProgress();
    }

    /**
     * Complete the usage (check-in).
     */
    public function checkIn(
        $checkedInAt,
        ?float $endReading = null,
        ?string $endPhotoPath = null,
        ?string $notes = null
    ): bool {
        if (! $this->canCheckIn()) {
            return false;
        }

        $data = [
            'status' => UsageLogStatus::Completed,
            'checked_in_at' => $checkedInAt,
            'end_reading' => $endReading,
            'end_photo_path' => $endPhotoPath,
            'end_notes' => $notes,
        ];

        // Calculate duration
        $data['duration_hours'] = $this->checked_out_at->diffInMinutes($checkedInAt) / 60;

        // Calculate distance if we have readings
        if ($this->start_reading !== null && $endReading !== null) {
            $data['distance_units'] = $endReading - $this->start_reading;
        }

        return $this->update($data);
    }

    /**
     * Verify the usage log.
     */
    public function verify(?int $verifiedBy = null, ?string $adminNotes = null): bool
    {
        if ($this->isInProgress()) {
            return false;
        }

        return $this->update([
            'status' => UsageLogStatus::Verified,
            'verified_by' => $verifiedBy ?? auth()->id(),
            'verified_at' => now(),
            'admin_notes' => $adminNotes,
        ]);
    }

    /**
     * Mark as disputed.
     */
    public function dispute(?string $adminNotes = null): bool
    {
        return $this->update([
            'status' => UsageLogStatus::Disputed,
            'admin_notes' => $adminNotes,
        ]);
    }

    /**
     * Delete associated photo files.
     */
    public function deletePhotos(): void
    {
        if ($this->start_photo_path) {
            Storage::disk('public')->delete($this->start_photo_path);
        }
        if ($this->end_photo_path) {
            Storage::disk('public')->delete($this->end_photo_path);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to filter by in progress.
     */
    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', UsageLogStatus::CheckedOut);
    }

    /**
     * Scope to filter by completed.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', UsageLogStatus::Completed);
    }

    /**
     * Scope to filter by verified.
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('status', UsageLogStatus::Verified);
    }

    /**
     * Scope to filter by disputed.
     */
    public function scopeDisputed(Builder $query): Builder
    {
        return $query->where('status', UsageLogStatus::Disputed);
    }

    /**
     * Scope to filter by billable.
     */
    public function scopeBillable(Builder $query): Builder
    {
        return $query->whereIn('status', [UsageLogStatus::Completed, UsageLogStatus::Verified]);
    }

    /**
     * Scope to filter by user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by resource.
     */
    public function scopeForResource(Builder $query, int $resourceId): Builder
    {
        return $query->where('resource_id', $resourceId);
    }

    /**
     * Scope to filter by reservation.
     */
    public function scopeForReservation(Builder $query, int $reservationId): Builder
    {
        return $query->where('reservation_id', $reservationId);
    }

    /**
     * Scope for pending verification.
     */
    public function scopePendingVerification(Builder $query): Builder
    {
        return $query->whereIn('status', [UsageLogStatus::Completed, UsageLogStatus::Disputed]);
    }
}
