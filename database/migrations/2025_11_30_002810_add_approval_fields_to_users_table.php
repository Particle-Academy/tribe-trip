<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds approval workflow and role fields to the users table.
 *
 * Part of Story 1: User Registration & Approval Flow
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Approval workflow fields
            $table->string('status')->default(UserStatus::Pending->value)->after('password');
            $table->timestamp('status_changed_at')->nullable()->after('status');
            $table->text('status_reason')->nullable()->after('status_changed_at');

            // Role for admin vs member distinction
            $table->string('role')->default(UserRole::Member->value)->after('status_reason');

            // Additional member info fields
            $table->string('phone')->nullable()->after('email');

            // Index for filtering by status and role
            $table->index(['status', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['status', 'role']);
            $table->dropColumn([
                'status',
                'status_changed_at',
                'status_reason',
                'role',
                'phone',
            ]);
        });
    }
};
