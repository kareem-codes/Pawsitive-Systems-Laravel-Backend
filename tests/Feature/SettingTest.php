<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ClinicSetting;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->staff = User::factory()->create();
        $this->staff->assignRole('staff');
    }

    public function test_can_list_all_settings()
    {
        ClinicSetting::create([
            'key' => 'clinic_name',
            'value' => 'Pawsitive Veterinary Clinic',
            'type' => 'string',
        ]);

        ClinicSetting::create([
            'key' => 'appointment_duration',
            'value' => '30',
            'type' => 'integer',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/settings');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_can_create_setting()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/settings', [
                'key' => 'business_hours_start',
                'value' => '09:00',
                'type' => 'string',
                'description' => 'Clinic opening time',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.key', 'business_hours_start')
            ->assertJsonPath('data.value', '09:00');

        $this->assertDatabaseHas('clinic_settings', [
            'key' => 'business_hours_start',
            'value' => '09:00',
        ]);
    }

    public function test_can_update_setting()
    {
        $setting = ClinicSetting::create([
            'key' => 'clinic_name',
            'value' => 'Old Clinic Name',
            'type' => 'string',
        ]);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/settings/{$setting->id}", [
                'value' => 'New Clinic Name',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.value', 'New Clinic Name');

        $this->assertDatabaseHas('clinic_settings', [
            'id' => $setting->id,
            'value' => 'New Clinic Name',
        ]);
    }

    public function test_can_delete_setting()
    {
        $setting = ClinicSetting::create([
            'key' => 'temp_setting',
            'value' => 'temporary',
            'type' => 'string',
        ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/settings/{$setting->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('clinic_settings', [
            'id' => $setting->id,
        ]);
    }

    public function test_can_get_setting_by_key()
    {
        ClinicSetting::create([
            'key' => 'clinic_email',
            'value' => 'contact@pawsitive.com',
            'type' => 'string',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/settings/key/clinic_email');

        $response->assertOk()
            ->assertJsonPath('data.key', 'clinic_email')
            ->assertJsonPath('data.value', 'contact@pawsitive.com');
    }

    public function test_can_batch_update_settings()
    {
        ClinicSetting::create([
            'key' => 'clinic_name',
            'value' => 'Old Name',
            'type' => 'string',
        ]);

        ClinicSetting::create([
            'key' => 'clinic_phone',
            'value' => '555-0000',
            'type' => 'string',
        ]);

        $response = $this->actingAs($this->admin)
            ->putJson('/api/settings/batch', [
                'settings' => [
                    ['key' => 'clinic_name', 'value' => 'Pawsitive Clinic'],
                    ['key' => 'clinic_phone', 'value' => '555-1234'],
                ],
            ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Settings updated successfully',
            ]);

        $this->assertDatabaseHas('clinic_settings', [
            'key' => 'clinic_name',
            'value' => 'Pawsitive Clinic',
        ]);

        $this->assertDatabaseHas('clinic_settings', [
            'key' => 'clinic_phone',
            'value' => '555-1234',
        ]);
    }

    public function test_setting_value_is_cast_by_type()
    {
        $intSetting = ClinicSetting::create([
            'key' => 'max_appointments',
            'value' => '10',
            'type' => 'integer',
        ]);

        $boolSetting = ClinicSetting::create([
            'key' => 'email_notifications',
            'value' => 'true',
            'type' => 'boolean',
        ]);

        $this->assertIsInt($intSetting->fresh()->value);
        $this->assertIsBool($boolSetting->fresh()->value);
    }

    public function test_staff_can_view_settings()
    {
        ClinicSetting::create([
            'key' => 'clinic_name',
            'value' => 'Pawsitive Clinic',
            'type' => 'string',
        ]);

        $response = $this->actingAs($this->staff)
            ->getJson('/api/settings');

        $response->assertOk();
    }

    public function test_staff_cannot_modify_settings()
    {
        $setting = ClinicSetting::create([
            'key' => 'clinic_name',
            'value' => 'Pawsitive Clinic',
            'type' => 'string',
        ]);

        $response = $this->actingAs($this->staff)
            ->putJson("/api/settings/{$setting->id}", [
                'value' => 'New Name',
            ]);

        $response->assertForbidden();
    }

    public function test_validation_fails_for_invalid_type()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/settings', [
                'key' => 'test_setting',
                'value' => 'test',
                'type' => 'invalid_type',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function test_cannot_create_duplicate_key()
    {
        ClinicSetting::create([
            'key' => 'unique_key',
            'value' => 'value1',
            'type' => 'string',
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/settings', [
                'key' => 'unique_key',
                'value' => 'value2',
                'type' => 'string',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['key']);
    }
}
