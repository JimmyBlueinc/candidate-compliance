<?php

namespace Database\Seeders;

use App\Models\Credential;
use App\Models\Organization;
use App\Models\OrganizationDomain;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     * 
     * NOTE: Seeding is disabled for production use.
     * All users must be created through the registration endpoint.
     * This ensures real-time authentication without test data.
     */
    public function run(): void
    {
        $org = Organization::firstOrCreate([
            'slug' => 'demo',
        ], [
            'name' => 'Demo Organization',
            'is_active' => true,
        ]);

        OrganizationDomain::firstOrCreate([
            'organization_id' => $org->id,
            'domain' => 'demo.localhost',
        ], [
            'is_active' => true,
        ]);

        User::firstOrCreate([
            'email' => 'platform_admin@goodwill.local',
        ], [
            'name' => 'Platform Admin',
            'password' => Hash::make('Password123!'),
            'role' => 'platform_admin',
            'organization_id' => null,
        ]);

        User::firstOrCreate([
            'email' => 'owner@demo.local',
        ], [
            'name' => 'Demo Org Owner',
            'password' => Hash::make('Password123!'),
            'role' => 'org_super_admin',
            'organization_id' => $org->id,
        ]);
    }
}
