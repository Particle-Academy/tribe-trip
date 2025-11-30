<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Seeder for creating test users with known credentials for browser testing.
 *
 * This seeder creates predictable test accounts that can be used for manual QA
 * and browser testing. These users have fixed credentials documented below.
 *
 * TEST CREDENTIALS:
 * - Admin: admin@test.local / testpassword123
 * - Member: member@test.local / testpassword123
 */
class TestUserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Known password for all test users.
     */
    public const TEST_PASSWORD = 'testpassword123';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createAdminUser();
        $this->createMemberUser();
    }

    /**
     * Create test admin user for browser testing.
     *
     * Credentials: admin@test.local / testpassword123
     */
    private function createAdminUser(): void
    {
        User::factory()->admin()->create([
            'name' => 'Test Admin',
            'email' => 'admin@test.local',
            'phone' => '555-000-0001',
            'password' => self::TEST_PASSWORD,
        ]);

        $this->command->info('Created test admin: admin@test.local');
    }

    /**
     * Create test member user for browser testing.
     *
     * Credentials: member@test.local / testpassword123
     */
    private function createMemberUser(): void
    {
        User::factory()->approved()->create([
            'name' => 'Test Member',
            'email' => 'member@test.local',
            'phone' => '555-000-0002',
            'password' => self::TEST_PASSWORD,
        ]);

        $this->command->info('Created test member: member@test.local');
    }
}
