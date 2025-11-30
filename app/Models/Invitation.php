<?php

namespace App\Models;

use App\Enums\InvitationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Invitation model for admin-generated member invites.
 *
 * Allows admins to invite users directly without approval process.
 */
class Invitation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'token',
        'email',
        'name',
        'status',
        'expires_at',
        'invited_by',
        'accepted_by',
        'accepted_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => InvitationStatus::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Boot Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Boot the model and register event listeners.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Generate token when creating new invitation
        static::creating(function (Invitation $invitation) {
            if (empty($invitation->token)) {
                $invitation->token = static::generateToken();
            }
        });
    }

    /**
     * Generate a unique invitation token.
     */
    public static function generateToken(): string
    {
        do {
            $token = Str::random(64);
        } while (static::where('token', $token)->exists());

        return $token;
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the admin who created the invitation.
     */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Get the user who accepted the invitation.
     */
    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Status Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if the invitation is pending and not expired.
     */
    public function isPending(): bool
    {
        return $this->status === InvitationStatus::Pending;
    }

    /**
     * Check if the invitation has been accepted.
     */
    public function isAccepted(): bool
    {
        return $this->status === InvitationStatus::Accepted;
    }

    /**
     * Check if the invitation has been revoked.
     */
    public function isRevoked(): bool
    {
        return $this->status === InvitationStatus::Revoked;
    }

    /**
     * Check if the invitation has expired.
     */
    public function isExpired(): bool
    {
        return $this->status === InvitationStatus::Expired
            || ($this->isPending() && $this->expires_at->isPast());
    }

    /**
     * Check if the invitation can still be used.
     */
    public function isUsable(): bool
    {
        return $this->isPending() && ! $this->expires_at->isPast();
    }

    /**
     * Mark the invitation as accepted by a user.
     */
    public function markAsAccepted(User $user): bool
    {
        return $this->update([
            'status' => InvitationStatus::Accepted,
            'accepted_by' => $user->id,
            'accepted_at' => now(),
        ]);
    }

    /**
     * Revoke the invitation.
     */
    public function revoke(): bool
    {
        if (! $this->isPending()) {
            return false;
        }

        return $this->update([
            'status' => InvitationStatus::Revoked,
        ]);
    }

    /**
     * Mark the invitation as expired.
     */
    public function markAsExpired(): bool
    {
        return $this->update([
            'status' => InvitationStatus::Expired,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to filter by pending status.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', InvitationStatus::Pending);
    }

    /**
     * Scope to filter by accepted status.
     */
    public function scopeAccepted(Builder $query): Builder
    {
        return $query->where('status', InvitationStatus::Accepted);
    }

    /**
     * Scope to filter invitations that are still usable.
     */
    public function scopeUsable(Builder $query): Builder
    {
        return $query->pending()->where('expires_at', '>', now());
    }

    /**
     * Scope to filter expired invitations.
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('status', InvitationStatus::Expired)
                ->orWhere(function ($q2) {
                    $q2->where('status', InvitationStatus::Pending)
                        ->where('expires_at', '<=', now());
                });
        });
    }

    /**
     * Find an invitation by its token.
     */
    public static function findByToken(string $token): ?static
    {
        return static::where('token', $token)->first();
    }

    /*
    |--------------------------------------------------------------------------
    | URL Generation
    |--------------------------------------------------------------------------
    */

    /**
     * Get the invitation URL.
     */
    public function getUrl(): string
    {
        return url("/register/invite/{$this->token}");
    }
}
