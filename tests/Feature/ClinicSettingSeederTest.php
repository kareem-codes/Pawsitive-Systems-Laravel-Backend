<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\ClinicSetting;
use Database\Seeders\ClinicSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ClinicSettingSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_all_default_settings()
    {
        $this->seed(ClinicSettingSeeder::class);

        // Verify essential settings exist
        $this->assertDatabaseHas('clinic_settings', [
            'key' => 'clinic_name',
            'value' => 'Pawsitive Veterinary Clinic',
        ]);

        $this->assertDatabaseHas('clinic_settings', [
            'key' => 'default_appointment_duration',
            'value' => '30',
            'type' => 'integer',
        ]);

        $this->assertDatabaseHas('clinic_settings', [
            'key' => 'send_appointment_reminders',
            'value' => 'true',
            'type' => 'boolean',
        ]);

        // Verify we have a reasonable number of settings
        $this->assertGreaterThanOrEqual(25, ClinicSetting::count());
    }

    public function test_seeder_does_not_duplicate_settings()
    {
        $this->seed(ClinicSettingSeeder::class);
        $firstCount = ClinicSetting::count();

        // Run seeder again
        $this->seed(ClinicSettingSeeder::class);
        $secondCount = ClinicSetting::count();

        // Count should remain the same
        $this->assertEquals($firstCount, $secondCount);
    }

    public function test_setting_types_are_correct()
    {
        $this->seed(ClinicSettingSeeder::class);

        // Check boolean settings
        $boolSetting = ClinicSetting::where('key', 'allow_online_booking')->first();
        $this->assertEquals('boolean', $boolSetting->type);

        // Check integer settings
        $intSetting = ClinicSetting::where('key', 'max_appointments_per_day')->first();
        $this->assertEquals('integer', $intSetting->type);

        // Check string settings
        $stringSetting = ClinicSetting::where('key', 'clinic_name')->first();
        $this->assertEquals('string', $stringSetting->type);
    }
}
