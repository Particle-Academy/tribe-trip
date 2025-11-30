<?php

use App\Enums\UsageLogStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the usage_logs table for tracking resource usage.
 *
 * Records check-out/check-in with odometer/meter readings and photos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resource_id')->constrained()->cascadeOnDelete();

            // Status
            $table->string('status')->default(UsageLogStatus::CheckedOut->value);

            // Check-out data
            $table->datetime('checked_out_at');
            $table->decimal('start_reading', 12, 2)->nullable(); // Odometer/meter reading
            $table->string('start_photo_path')->nullable(); // Photo of meter at start
            $table->text('start_notes')->nullable();

            // Check-in data
            $table->datetime('checked_in_at')->nullable();
            $table->decimal('end_reading', 12, 2)->nullable(); // Odometer/meter reading
            $table->string('end_photo_path')->nullable(); // Photo of meter at end
            $table->text('end_notes')->nullable();

            // Calculated usage
            $table->decimal('duration_hours', 10, 2)->nullable(); // Time used
            $table->decimal('distance_units', 12, 2)->nullable(); // Miles/km used
            $table->decimal('calculated_cost', 10, 2)->nullable(); // Cost based on usage

            // Admin verification
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->datetime('verified_at')->nullable();
            $table->text('admin_notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['reservation_id']);
            $table->index(['user_id', 'status']);
            $table->index(['resource_id', 'checked_out_at']);

            // Foreign keys
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_logs');
    }
};
