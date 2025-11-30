<?php

use App\Enums\PricingModel;
use App\Enums\PricingUnit;
use App\Enums\ResourceStatus;
use App\Enums\ResourceType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the resources table for community-shared resources.
 *
 * Resources can be vehicles, equipment, spaces, etc. with flexible pricing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->default(ResourceType::Other->value);
            $table->string('status')->default(ResourceStatus::Active->value);

            // Pricing configuration
            $table->string('pricing_model')->default(PricingModel::FlatFee->value);
            $table->decimal('rate', 10, 2)->default(0.00);
            $table->string('pricing_unit')->nullable(); // Only for per_unit pricing

            // Availability settings
            $table->boolean('requires_approval')->default(false);
            $table->integer('max_reservation_days')->nullable(); // Max days per reservation
            $table->integer('advance_booking_days')->nullable(); // How far ahead can book

            // Tracking
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['status', 'type']);
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};
