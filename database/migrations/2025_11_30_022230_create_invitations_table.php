<?php

use App\Enums\InvitationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the invitations table for admin-generated member invites.
 *
 * Part of Story 2: Admin Invitation System
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();

            // Unique token for invitation URL
            $table->string('token', 64)->unique();

            // Email being invited
            $table->string('email');

            // Optional name for personalized invites
            $table->string('name')->nullable();

            // Invitation status tracking
            $table->string('status')->default(InvitationStatus::Pending->value);

            // When the invitation expires
            $table->timestamp('expires_at');

            // Admin who created the invitation
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();

            // User who accepted the invitation (null until accepted)
            $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();

            // When the invitation was accepted
            $table->timestamp('accepted_at')->nullable();

            $table->timestamps();

            // Index for looking up invitations by email
            $table->index('email');
            $table->index(['status', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
