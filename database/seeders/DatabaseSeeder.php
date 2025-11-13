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
        $this->call([
            RolePermissionSeeder::class,
            ClinicSettingSeeder::class,
        ]);

        // Seed demo data if requested
        // Run with: php artisan db:seed --class=DemoDataSeeder
        // Or uncomment the line below to include it in default seeding
        // $this->call(DemoDataSeeder::class);
    }
}
