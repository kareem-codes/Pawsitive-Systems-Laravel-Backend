<?php

namespace Tests\Feature;

use App\Models\Pet;
use App\Models\User;
use App\Models\Vaccination;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VaccinationTest extends TestCase
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

    public function test_vet_can_create_vaccination_record(): void
    {
        $vaccinationData = [
            'pet_id' => $this->pet->id,
            'veterinarian_id' => $this->vet->id,
            'vaccine_name' => 'Rabies',
            'administered_date' => now()->format('Y-m-d'),
            'next_due_date' => now()->addYear()->format('Y-m-d'),
            'batch_number' => 'BATCH123',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->vetToken)
            ->postJson('/api/v1/vaccinations', $vaccinationData);

        $response->assertStatus(201)
            ->assertJson([
                'vaccination' => [
                    'vaccine_name' => 'Rabies',
                    'pet_id' => $this->pet->id,
                ]
            ]);

        $this->assertDatabaseHas('vaccinations', [
            'pet_id' => $this->pet->id,
            'vaccine_name' => 'Rabies',
        ]);
    }

    public function test_owner_can_view_their_pet_vaccinations(): void
    {
        Vaccination::factory()->count(3)->create([
            'pet_id' => $this->pet->id,
            'veterinarian_id' => $this->vet->id,
        ]);

        $otherPet = Pet::factory()->create();
        Vaccination::factory()->count(2)->create([
            'pet_id' => $otherPet->id,
            'veterinarian_id' => $this->vet->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->getJson('/api/v1/vaccinations');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_get_vaccinations_due_soon(): void
    {
        Vaccination::factory()->create([
            'pet_id' => $this->pet->id,
            'veterinarian_id' => $this->vet->id,
            'next_due_date' => now()->addDays(10),
        ]);
        Vaccination::factory()->create([
            'pet_id' => $this->pet->id,
            'veterinarian_id' => $this->vet->id,
            'next_due_date' => now()->addMonths(6),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->getJson('/api/v1/vaccinations?due_soon=true');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_get_overdue_vaccinations(): void
    {
        Vaccination::factory()->create([
            'pet_id' => $this->pet->id,
            'veterinarian_id' => $this->vet->id,
            'next_due_date' => now()->subDays(10),
        ]);
        Vaccination::factory()->create([
            'pet_id' => $this->pet->id,
            'veterinarian_id' => $this->vet->id,
            'next_due_date' => now()->addDays(10),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->getJson('/api/v1/vaccinations?overdue=true');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_filter_vaccinations_by_pet(): void
    {
        $otherPet = Pet::factory()->create(['user_id' => $this->owner->id]);

        Vaccination::factory()->count(2)->create([
            'pet_id' => $this->pet->id,
            'veterinarian_id' => $this->vet->id,
        ]);
        Vaccination::factory()->count(3)->create([
            'pet_id' => $otherPet->id,
            'veterinarian_id' => $this->vet->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->getJson("/api/v1/vaccinations?pet_id={$this->pet->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_vet_can_update_vaccination_record(): void
    {
        $vaccination = Vaccination::factory()->create([
            'pet_id' => $this->pet->id,
            'veterinarian_id' => $this->vet->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->vetToken)
            ->putJson("/api/v1/vaccinations/{$vaccination->id}", [
                'pet_id' => $this->pet->id,
                'veterinarian_id' => $this->vet->id,
                'vaccine_name' => 'Updated Vaccine',
                'administered_date' => $vaccination->administered_date,
                'next_due_date' => now()->addYear()->format('Y-m-d'),
            ]);

        $response->assertStatus(200)
            ->assertJson(['vaccination' => ['vaccine_name' => 'Updated Vaccine']]);

        $this->assertDatabaseHas('vaccinations', [
            'id' => $vaccination->id,
            'vaccine_name' => 'Updated Vaccine',
        ]);
    }

    public function test_vet_can_delete_vaccination_record(): void
    {
        $vaccination = Vaccination::factory()->create([
            'pet_id' => $this->pet->id,
            'veterinarian_id' => $this->vet->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->vetToken)
            ->deleteJson("/api/v1/vaccinations/{$vaccination->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('vaccinations', ['id' => $vaccination->id]);
    }

    public function test_validation_fails_for_invalid_dates(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->vetToken)
            ->postJson('/api/v1/vaccinations', [
                'pet_id' => $this->pet->id,
                'veterinarian_id' => $this->vet->id,
                'vaccine_name' => 'Rabies',
                'administered_date' => now()->addDays(1)->format('Y-m-d'),
                'next_due_date' => now()->format('Y-m-d'),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['administered_date', 'next_due_date']);
    }
}
