<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicalRecordTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $vet;
    protected Pet $pet;
    protected string $ownerToken;
    protected string $vetToken;

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

        $this->pet = Pet::factory()->create(['user_id' => $this->owner->id]);
    }

    public function test_vet_can_create_medical_record(): void
    {
        $appointment = Appointment::factory()->create([
            'pet_id' => $this->pet->id,
            'veterinarian_id' => $this->vet->id,
        ]);

        $recordData = [
            'pet_id' => $this->pet->id,
            'veterinarian_id' => $this->vet->id,
            'appointment_id' => $appointment->id,
            'visit_date' => now()->format('Y-m-d'),
            'diagnosis' => 'Healthy pet',
            'treatment' => 'Regular checkup completed',
            'weight' => 5.5,
            'temperature' => 38.5,
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->vetToken)
            ->postJson('/api/v1/medical-records', $recordData);

        $response->assertStatus(201)
            ->assertJson([
                'medical_record' => [
                    'diagnosis' => 'Healthy pet',
                    'pet_id' => $this->pet->id,
                ]
            ]);

        $this->assertDatabaseHas('medical_records', [
            'pet_id' => $this->pet->id,
            'diagnosis' => 'Healthy pet',
        ]);
    }

    public function test_owner_can_view_their_pet_medical_records(): void
    {
        MedicalRecord::factory()->count(3)->create([
            'pet_id' => $this->pet->id,
            'veterinarian_id' => $this->vet->id,
        ]);

        $otherPet = Pet::factory()->create();
        MedicalRecord::factory()->count(2)->create([
            'pet_id' => $otherPet->id,
            'veterinarian_id' => $this->vet->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->getJson('/api/v1/medical-records');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_owner_cannot_view_other_pet_medical_record(): void
    {
        $otherPet = Pet::factory()->create();
        $record = MedicalRecord::factory()->create([
            'pet_id' => $otherPet->id,
            'veterinarian_id' => $this->vet->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->getJson("/api/v1/medical-records/{$record->id}");

        $response->assertStatus(403);
    }

    public function test_vet_can_update_medical_record(): void
    {
        $record = MedicalRecord::factory()->create([
            'pet_id' => $this->pet->id,
            'veterinarian_id' => $this->vet->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->vetToken)
            ->putJson("/api/v1/medical-records/{$record->id}", [
                'pet_id' => $this->pet->id,
                'veterinarian_id' => $this->vet->id,
                'visit_date' => $record->visit_date,
                'diagnosis' => 'Updated diagnosis',
                'treatment' => 'Updated treatment',
            ]);

        $response->assertStatus(200)
            ->assertJson(['medical_record' => ['diagnosis' => 'Updated diagnosis']]);

        $this->assertDatabaseHas('medical_records', [
            'id' => $record->id,
            'diagnosis' => 'Updated diagnosis',
        ]);
    }

    public function test_filter_medical_records_by_pet(): void
    {
        $otherPet = Pet::factory()->create(['user_id' => $this->owner->id]);

        MedicalRecord::factory()->count(2)->create([
            'pet_id' => $this->pet->id,
            'veterinarian_id' => $this->vet->id,
        ]);
        MedicalRecord::factory()->count(3)->create([
            'pet_id' => $otherPet->id,
            'veterinarian_id' => $this->vet->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->getJson("/api/v1/medical-records?pet_id={$this->pet->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_filter_medical_records_by_date(): void
    {
        MedicalRecord::factory()->create([
            'pet_id' => $this->pet->id,
            'veterinarian_id' => $this->vet->id,
            'visit_date' => now()->format('Y-m-d'),
        ]);
        MedicalRecord::factory()->create([
            'pet_id' => $this->pet->id,
            'veterinarian_id' => $this->vet->id,
            'visit_date' => now()->subDays(10)->format('Y-m-d'),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->getJson('/api/v1/medical-records?from_date=' . now()->subDays(1)->format('Y-m-d'));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_vet_can_delete_medical_record(): void
    {
        $record = MedicalRecord::factory()->create([
            'pet_id' => $this->pet->id,
            'veterinarian_id' => $this->vet->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->vetToken)
            ->deleteJson("/api/v1/medical-records/{$record->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('medical_records', ['id' => $record->id]);
    }

    public function test_validation_fails_for_missing_required_fields(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->vetToken)
            ->postJson('/api/v1/medical-records', [
                'pet_id' => $this->pet->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['veterinarian_id', 'visit_date']);
    }
}
