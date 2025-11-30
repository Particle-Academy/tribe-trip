<?php

namespace App\Enums;

/**
 * User role enum.
 *
 * Defines access levels within the community.
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Member = 'member';

    /**
     * Get a human-readable label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Member => 'Member',
        };
    }

    /**
     * Check if this role has admin privileges.
     */
    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }
}

