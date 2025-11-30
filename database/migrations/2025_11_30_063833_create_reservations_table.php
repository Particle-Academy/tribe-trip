<?php

use App\Enums\ReservationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the reservations table for resource bookings.
 *
 * Tracks member reservations with time slots and status lifecycle.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Time slot
            $table->datetime('starts_at');
            $table->datetime('ends_at');

            // Status tracking
            $table->string('status')->default(ReservationStatus::Pending->value);
            $table->text('notes')->nullable(); // Member notes for the reservation
            $table->text('admin_notes')->nullable(); // Admin notes (visible to admin only)

            // Cancellation tracking
            $table->datetime('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();

            // Confirmation tracking
            $table->datetime('confirmed_at')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();

            $table->timestamps();

            // Indexes for common queries
            $table->index(['resource_id', 'starts_at', 'ends_at']);
            $table->index(['user_id', 'status']);
            $table->index(['status', 'starts_at']);

            // Foreign keys for audit trail
            $table->foreign('cancelled_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('confirmed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
