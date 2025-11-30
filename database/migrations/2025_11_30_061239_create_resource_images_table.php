<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the resource_images table for resource photos.
 *
 * Supports multiple images per resource with ordering.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('filename');
            $table->integer('order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            // Indexes
            $table->index(['resource_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_images');
    }
};
