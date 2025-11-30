<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * User model for TribeTrip community members.
 *
 * Handles authentication, approval workflow, and role-based access.
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'status',
        'status_changed_at',
        'status_reason',
        'role',
        'profile_photo_path',
        'notification_preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'status_changed_at' => 'datetime',
            'role' => UserRole::class,
            'notification_preferences' => 'array',
        ];
    }

    /**
     * Get the URL to the user's profile photo.
     */
    public function getProfilePhotoUrlAttribute(): ?string
    {
        if ($this->profile_photo_path) {
            return asset('storage/' . $this->profile_photo_path);
        }

        return null;
    }

    /**
     * Get the user's notification preferences with defaults.
     */
    public function getNotificationSetting(string $key, bool $default = true): bool
    {
        $preferences = $this->notification_preferences ?? [];

        return $preferences[$key] ?? $default;
    }

    /**
     * Update a specific notification preference.
     */
    public function setNotificationSetting(string $key, bool $value): bool
    {
        $preferences = $this->notification_preferences ?? [];
        $preferences[$key] = $value;

        return $this->update(['notification_preferences' => $preferences]);
    }

    /**
     * Default notification preferences for new users.
     */
    public static function defaultNotificationPreferences(): array
    {
        return [
            'email_reservation_confirmations' => true,
            'email_reservation_reminders' => true,
            'email_invoice_notifications' => true,
            'email_community_announcements' => true,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the user's reservations.
     */
    public function reservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Get the user's invoices.
     */
    public function invoices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the user's usage logs.
     */
    public function usageLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UsageLog::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Approval Workflow Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if the user is pending approval.
     */
    public function isPending(): bool
    {
        return $this->status === UserStatus::Pending;
    }

    /**
     * Check if the user is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === UserStatus::Approved;
    }

    /**
     * Check if the user is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === UserStatus::Rejected;
    }

    /**
     * Approve the user's registration.
     */
    public function approve(?string $reason = null): bool
    {
        return $this->updateStatus(UserStatus::Approved, $reason);
    }

    /**
     * Reject the user's registration.
     */
    public function reject(?string $reason = null): bool
    {
        return $this->updateStatus(UserStatus::Rejected, $reason);
    }

    /**
     * Suspend the user's account.
     */
    public function suspend(?string $reason = null): bool
    {
        return $this->updateStatus(UserStatus::Suspended, $reason);
    }

    /**
     * Check if the user is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === UserStatus::Suspended;
    }

    /**
     * Reactivate a suspended or rejected user.
     */
    public function reactivate(?string $reason = null): bool
    {
        return $this->updateStatus(UserStatus::Approved, $reason);
    }

    /**
     * Update the user's status with tracking.
     */
    protected function updateStatus(UserStatus $status, ?string $reason = null): bool
    {
        return $this->update([
            'status' => $status,
            'status_changed_at' => now(),
            'status_reason' => $reason,
        ]);
    }

    /**
     * Check if the user can access the application.
     */
    public function canAccessApp(): bool
    {
        return $this->status->canAccessApp();
    }

    /*
    |--------------------------------------------------------------------------
    | Role Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role->isAdmin();
    }

    /**
     * Check if the user is a regular member.
     */
    public function isMember(): bool
    {
        return $this->role === UserRole::Member;
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to filter by pending approval status.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', UserStatus::Pending);
    }

    /**
     * Scope to filter by approved status.
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', UserStatus::Approved);
    }

    /**
     * Scope to filter by rejected status.
     */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', UserStatus::Rejected);
    }

    /**
     * Scope to filter by admin role.
     */
    public function scopeAdmins(Builder $query): Builder
    {
        return $query->where('role', UserRole::Admin);
    }

    /**
     * Scope to filter by member role.
     */
    public function scopeMembers(Builder $query): Builder
    {
        return $query->where('role', UserRole::Member);
    }

    /**
     * Scope to filter by suspended status.
     */
    public function scopeSuspended(Builder $query): Builder
    {
        return $query->where('status', UserStatus::Suspended);
    }

    /**
     * Scope to filter active members (approved or suspended, not pending/rejected).
     */
    public function scopeActiveMembers(Builder $query): Builder
    {
        return $query->whereIn('status', [UserStatus::Approved, UserStatus::Suspended]);
    }

    /*
    |--------------------------------------------------------------------------
    | Role Management
    |--------------------------------------------------------------------------
    */

    /**
     * Promote the user to admin role.
     */
    public function promoteToAdmin(): bool
    {
        return $this->update(['role' => UserRole::Admin]);
    }

    /**
     * Demote the user to member role.
     */
    public function demoteToMember(): bool
    {
        return $this->update(['role' => UserRole::Member]);
    }
}
