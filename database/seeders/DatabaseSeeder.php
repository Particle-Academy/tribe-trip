<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call TestUserSeeder to create test accounts for browser testing.
        $this->call(TestUserSeeder::class);

        // Call SampleDataSeeder to create sample users, resources, and usage history.
        $this->call(SampleDataSeeder::class);
    }
}
