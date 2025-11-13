<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $vet;
    protected User $receptionist;
    protected Pet $pet;
    protected string $ownerToken;
    protected string $vetToken;
    protected string $receptionistToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $this->owner = User::factory()->owner()->create();
        $this->owner->assignRole('owner');
        $this->ownerToken = $this->owner->createToken('test-token')->plainTextToken;

        $this->vet = User::factory()->veterinarian()->create();
        $this->vet->assignRole('veterinarian');
        $this->vetToken = $this->vet->createToken('test-token')->plainTextToken;

        $this->receptionist = User::factory()->receptionist()->create();
        $this->receptionist->assignRole('receptionist');
        $this->receptionistToken = $this->receptionist->createToken('test-token')->plainTextToken;

        $this->pet = Pet::factory()->create(['user_id' => $this->owner->id]);
    }

    public function test_receptionist_can_create_appointment(): void
    {
        $appointmentData = [
            'pet_id' => $this->pet->id,
            'user_id' => $this->owner->id,
            'veterinarian_id' => $this->vet->id,
            'appointment_date' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'type' => 'checkup',
            'reason' => 'Regular checkup',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->receptionistToken)
            ->postJson('/api/v1/appointments', $appointmentData);

        $response->assertStatus(201)
            ->assertJson([
                'appointment' => [
                    'pet_id' => $this->pet->id,
                    'type' => 'checkup',
                ]
            ]);

        $this->assertDatabaseHas('appointments', [
            'pet_id' => $this->pet->id,
            'status' => 'pending',
        ]);
    }

    public function test_owner_can_view_their_appointments(): void
    {
        Appointment::factory()->count(3)->create([
            'pet_id' => $this->pet->id,
            'veterinarian_id' => $this->vet->id,
        ]);

        $otherPet = Pet::factory()->create();
        Appointment::factory()->count(2)->create([
            'pet_id' => $otherPet->id,
            'veterinarian_id' => $this->vet->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->getJson('/api/v1/appointments');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_vet_can_view_their_appointments(): void
    {
        Appointment::factory()->count(3)->create(['veterinarian_id' => $this->vet->id]);
        
        $otherVet = User::factory()->veterinarian()->create();
        Appointment::factory()->count(2)->create(['veterinarian_id' => $otherVet->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->vetToken)
            ->getJson('/api/v1/appointments?veterinarian_id=' . $this->vet->id);

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_filter_appointments_by_status(): void
    {
        Appointment::factory()->create([
            'pet_id' => $this->pet->id,
            'veterinarian_id' => $this->vet->id,
            'status' => 'pending',
        ]);
        Appointment::factory()->create([
            'pet_id' => $this->pet->id,
            'veterinarian_id' => $this->vet->id,
            'status' => 'completed',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->getJson('/api/v1/appointments?status=pending');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'pending');
    }

    public function test_get_upcoming_appointments(): void
    {
        Appointment::factory()->create([
            'pet_id' => $this->pet->id,
            'veterinarian_id' => $this->vet->id,
            'appointment_date' => now()->addDays(2),
            'status' => 'pending',
        ]);
        Appointment::factory()->create([
            'pet_id' => $this->pet->id,
            'veterinarian_id' => $this->vet->id,
            'appointment_date' => now()->subDays(2),
            'status' => 'completed',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->getJson('/api/v1/appointments?upcoming=true');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_receptionist_can_update_appointment_status(): void
    {
        $appointment = Appointment::factory()->create([
            'pet_id' => $this->pet->id,
            'veterinarian_id' => $this->vet->id,
            'status' => 'pending',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->receptionistToken)
            ->putJson("/api/v1/appointments/{$appointment->id}", [
                'pet_id' => $this->pet->id,
                'user_id' => $this->owner->id,
                'veterinarian_id' => $this->vet->id,
                'appointment_date' => $appointment->appointment_date,
                'type' => $appointment->type,
                'status' => 'confirmed',
            ]);

        $response->assertStatus(200)
            ->assertJson(['appointment' => ['status' => 'confirmed']]);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_receptionist_can_cancel_appointment(): void
    {
        $appointment = Appointment::factory()->create([
            'pet_id' => $this->pet->id,
            'veterinarian_id' => $this->vet->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->receptionistToken)
            ->deleteJson("/api/v1/appointments/{$appointment->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Appointment deleted successfully']);
        $this->assertSoftDeleted('appointments', ['id' => $appointment->id]);
    }

    public function test_validation_fails_for_past_appointment_date(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->receptionistToken)
            ->postJson('/api/v1/appointments', [
                'pet_id' => $this->pet->id,
                'veterinarian_id' => $this->vet->id,
                'appointment_date' => now()->subDays(1)->format('Y-m-d H:i:s'),
                'type' => 'checkup',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['appointment_date']);
    }

    public function test_can_get_available_time_slots(): void
    {
        $date = now()->addDays(2)->format('Y-m-d');

        // Create an appointment at 10:00 AM
        Appointment::factory()->create([
            'veterinarian_id' => $this->vet->id,
            'appointment_date' => now()->addDays(2)->setTime(10, 0, 0),
            'duration_minutes' => 30,
            'status' => 'confirmed',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->receptionistToken)
            ->getJson("/api/v1/appointments/slots/available?date={$date}&veterinarian_id={$this->vet->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'date',
                'veterinarian_id',
                'available_slots' => [
                    '*' => ['time', 'datetime']
                ]
            ]);

        // 10:00 slot should not be in available slots
        $slots = $response->json('available_slots');
        $times = array_column($slots, 'time');
        $this->assertNotContains('10:00', $times);
        $this->assertContains('09:00', $times); // 9:00 should be available
        $this->assertContains('11:00', $times); // 11:00 should be available
    }

    public function test_can_check_specific_slot_availability(): void
    {
        $appointmentDate = now()->addDays(2)->setTime(10, 0, 0);

        // Create an appointment at 10:00 AM
        Appointment::factory()->create([
            'veterinarian_id' => $this->vet->id,
            'appointment_date' => $appointmentDate,
            'duration_minutes' => 30,
            'status' => 'confirmed',
        ]);

        // Check if 10:00 slot is available (should be false)
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->receptionistToken)
            ->postJson('/api/v1/appointments/slots/check', [
                'appointment_date' => $appointmentDate->format('Y-m-d H:i:s'),
                'veterinarian_id' => $this->vet->id,
                'duration_minutes' => 30,
            ]);

        $response->assertStatus(200)
            ->assertJson(['available' => false])
            ->assertJsonStructure(['conflicts']);

        // Check if 11:00 slot is available (should be true)
        $availableSlot = now()->addDays(2)->setTime(11, 0, 0);
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->receptionistToken)
            ->postJson('/api/v1/appointments/slots/check', [
                'appointment_date' => $availableSlot->format('Y-m-d H:i:s'),
                'veterinarian_id' => $this->vet->id,
                'duration_minutes' => 30,
            ]);

        $response->assertStatus(200)
            ->assertJson(['available' => true]);
    }

    public function test_cannot_create_appointment_with_time_conflict(): void
    {
        $appointmentDate = now()->addDays(2)->setTime(10, 0, 0);

        // Create first appointment at 10:00 AM
        Appointment::factory()->create([
            'veterinarian_id' => $this->vet->id,
            'appointment_date' => $appointmentDate,
            'duration_minutes' => 30,
            'status' => 'confirmed',
        ]);

        // Try to create overlapping appointment
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->receptionistToken)
            ->postJson('/api/v1/appointments', [
                'pet_id' => $this->pet->id,
                'user_id' => $this->owner->id,
                'veterinarian_id' => $this->vet->id,
                'appointment_date' => $appointmentDate->format('Y-m-d H:i:s'),
                'duration_minutes' => 30,
                'type' => 'checkup',
                'reason' => 'Test appointment',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['appointment_date']);
    }

    public function test_can_update_appointment_to_non_conflicting_time(): void
    {
        $appointment = Appointment::factory()->create([
            'pet_id' => $this->pet->id,
            'veterinarian_id' => $this->vet->id,
            'appointment_date' => now()->addDays(2)->setTime(10, 0, 0),
            'duration_minutes' => 30,
        ]);

        // Create another appointment at 11:00
        Appointment::factory()->create([
            'veterinarian_id' => $this->vet->id,
            'appointment_date' => now()->addDays(2)->setTime(11, 0, 0),
            'duration_minutes' => 30,
            'status' => 'confirmed',
        ]);

        // Update to 14:00 (should succeed)
        $newDate = now()->addDays(2)->setTime(14, 0, 0);
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->receptionistToken)
            ->putJson("/api/v1/appointments/{$appointment->id}", [
                'appointment_date' => $newDate->format('Y-m-d H:i:s'),
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'appointment_date' => $newDate,
        ]);
    }

    public function test_cancelled_appointments_dont_block_time_slots(): void
    {
        $appointmentDate = now()->addDays(2)->setTime(10, 0, 0);

        // Create cancelled appointment at 10:00 AM
        Appointment::factory()->create([
            'veterinarian_id' => $this->vet->id,
            'appointment_date' => $appointmentDate,
            'duration_minutes' => 30,
            'status' => 'cancelled',
        ]);

        // Check if slot is available (should be true since appointment is cancelled)
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->receptionistToken)
            ->postJson('/api/v1/appointments/slots/check', [
                'appointment_date' => $appointmentDate->format('Y-m-d H:i:s'),
                'veterinarian_id' => $this->vet->id,
                'duration_minutes' => 30,
            ]);

        $response->assertStatus(200)
            ->assertJson(['available' => true]);
    }
}
