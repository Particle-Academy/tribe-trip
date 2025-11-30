<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds profile photo and notification preferences fields to users table.
 *
 * Supports member profile management: photo uploads and notification settings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Profile photo path for uploaded images
            $table->string('profile_photo_path')->nullable()->after('phone');

            // JSON field for notification preferences (email alerts, etc.)
            $table->json('notification_preferences')->nullable()->after('profile_photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['profile_photo_path', 'notification_preferences']);
        });
    }
};
