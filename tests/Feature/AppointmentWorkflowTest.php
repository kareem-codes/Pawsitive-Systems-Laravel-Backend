<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Pet;
use App\Models\Appointment;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AppointmentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected $staff;
    protected $owner;
    protected $pet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->staff = User::factory()->create();
        $this->staff->assignRole('staff');

        $this->owner = User::factory()->create();
        $this->owner->assignRole('owner');

        $this->pet = Pet::factory()->create([
            'owner_id' => $this->owner->id,
        ]);
    }

    public function test_can_confirm_appointment()
    {
        $appointment = Appointment::factory()->create([
            'pet_id' => $this->pet->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->staff)
            ->putJson("/api/appointments/{$appointment->id}/confirm");

        $response->assertOk()
            ->assertJson([
                'message' => 'Appointment confirmed successfully',
            ]);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_can_start_appointment()
    {
        $appointment = Appointment::factory()->create([
            'pet_id' => $this->pet->id,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->staff)
            ->putJson("/api/appointments/{$appointment->id}/start");

        $response->assertOk()
            ->assertJson([
                'message' => 'Appointment started successfully',
            ]);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'in-progress',
        ]);
    }

    public function test_can_complete_appointment()
    {
        $appointment = Appointment::factory()->create([
            'pet_id' => $this->pet->id,
            'status' => 'in-progress',
        ]);

        $response = $this->actingAs($this->staff)
            ->putJson("/api/appointments/{$appointment->id}/complete", [
                'notes' => 'Appointment completed successfully',
            ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Appointment completed successfully',
            ]);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'completed',
        ]);
    }

    public function test_can_cancel_appointment()
    {
        $appointment = Appointment::factory()->create([
            'pet_id' => $this->pet->id,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->staff)
            ->putJson("/api/appointments/{$appointment->id}/cancel", [
                'reason' => 'Client requested cancellation',
            ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Appointment cancelled successfully',
            ]);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_can_mark_appointment_as_no_show()
    {
        $appointment = Appointment::factory()->create([
            'pet_id' => $this->pet->id,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->staff)
            ->putJson("/api/appointments/{$appointment->id}/no-show");

        $response->assertOk()
            ->assertJson([
                'message' => 'Appointment marked as no-show',
            ]);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'no-show',
        ]);
    }

    public function test_cannot_start_pending_appointment()
    {
        $appointment = Appointment::factory()->create([
            'pet_id' => $this->pet->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->staff)
            ->putJson("/api/appointments/{$appointment->id}/start");

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Cannot start appointment with status: pending',
            ]);
    }

    public function test_cannot_complete_pending_appointment()
    {
        $appointment = Appointment::factory()->create([
            'pet_id' => $this->pet->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->staff)
            ->putJson("/api/appointments/{$appointment->id}/complete");

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Cannot complete appointment with status: pending',
            ]);
    }

    public function test_cannot_modify_completed_appointment()
    {
        $appointment = Appointment::factory()->create([
            'pet_id' => $this->pet->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->staff)
            ->putJson("/api/appointments/{$appointment->id}/confirm");

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Cannot modify completed appointment',
            ]);
    }

    public function test_owner_can_view_appointment_status()
    {
        $appointment = Appointment::factory()->create([
            'pet_id' => $this->pet->id,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson("/api/appointments/{$appointment->id}");

        $response->assertOk()
            ->assertJsonPath('data.status', 'confirmed');
    }

    public function test_owner_cannot_change_appointment_status()
    {
        $appointment = Appointment::factory()->create([
            'pet_id' => $this->pet->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->owner)
            ->putJson("/api/appointments/{$appointment->id}/confirm");

        $response->assertForbidden();
    }
}
