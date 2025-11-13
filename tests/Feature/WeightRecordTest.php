<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Pet;
use App\Models\WeightRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeightRecordTest extends TestCase
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

    public function test_can_create_weight_record(): void
    {
        $weightData = [
            'weight' => 15.5,
            'unit' => 'kg',
            'measured_at' => now()->format('Y-m-d'),
            'notes' => 'Healthy weight',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->vetToken)
            ->postJson("/api/v1/pets/{$this->pet->id}/weight", $weightData);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Weight record created successfully',
                'record' => [
                    'weight' => '15.50',
                    'unit' => 'kg',
                ]
            ]);

        $this->assertDatabaseHas('weight_records', [
            'pet_id' => $this->pet->id,
            'weight' => 15.50,
            'unit' => 'kg',
        ]);
    }

    public function test_can_list_weight_records(): void
    {
        WeightRecord::factory()->count(5)->create([
            'pet_id' => $this->pet->id,
            'recorded_by' => $this->vet->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->getJson("/api/v1/pets/{$this->pet->id}/weight");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'weight', 'unit', 'measured_at', 'notes']
                ]
            ]);
    }

    public function test_can_update_weight_record(): void
    {
        $record = WeightRecord::factory()->create([
            'pet_id' => $this->pet->id,
            'weight' => 10.0,
            'recorded_by' => $this->vet->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->vetToken)
            ->putJson("/api/v1/pets/{$this->pet->id}/weight/{$record->id}", [
                'weight' => 12.5,
                'notes' => 'Weight increased',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Weight record updated successfully',
            ]);

        $this->assertDatabaseHas('weight_records', [
            'id' => $record->id,
            'weight' => 12.50,
        ]);
    }

    public function test_can_delete_weight_record(): void
    {
        $record = WeightRecord::factory()->create([
            'pet_id' => $this->pet->id,
            'recorded_by' => $this->vet->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->vetToken)
            ->deleteJson("/api/v1/pets/{$this->pet->id}/weight/{$record->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Weight record deleted successfully']);

        $this->assertDatabaseMissing('weight_records', ['id' => $record->id]);
    }

    public function test_can_get_weight_analytics(): void
    {
        // Create weight records with increasing weight
        WeightRecord::factory()->create([
            'pet_id' => $this->pet->id,
            'weight' => 10.0,
            'measured_at' => now()->subDays(30),
            'recorded_by' => $this->vet->id,
        ]);

        WeightRecord::factory()->create([
            'pet_id' => $this->pet->id,
            'weight' => 12.0,
            'measured_at' => now()->subDays(15),
            'recorded_by' => $this->vet->id,
        ]);

        WeightRecord::factory()->create([
            'pet_id' => $this->pet->id,
            'weight' => 15.0,
            'measured_at' => now(),
            'recorded_by' => $this->vet->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->getJson("/api/v1/pets/{$this->pet->id}/weight/analytics");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_records',
                'first_weight',
                'last_weight',
                'weight_change',
                'percentage_change',
                'average_weight',
                'min_weight',
                'max_weight',
                'trend',
                'chart_data',
            ])
            ->assertJson([
                'total_records' => 3,
                'first_weight' => 10.0,
                'last_weight' => 15.0,
                'weight_change' => 5.0,
                'trend' => 'increasing',
            ]);
    }

    public function test_weight_record_updates_pet_current_weight(): void
    {
        $weightData = [
            'weight' => 20.0,
            'unit' => 'kg',
            'measured_at' => now()->format('Y-m-d'),
        ];

        $this->withHeader('Authorization', 'Bearer ' . $this->vetToken)
            ->postJson("/api/v1/pets/{$this->pet->id}/weight", $weightData);

        $this->pet->refresh();
        $this->assertEquals(20.0, $this->pet->weight);
    }

    public function test_cannot_add_weight_record_for_other_owners_pet(): void
    {
        $otherOwner = User::factory()->owner()->create();
        $otherOwner->assignRole('owner');
        $otherPet = Pet::factory()->create(['user_id' => $otherOwner->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->postJson("/api/v1/pets/{$otherPet->id}/weight", [
                'weight' => 10.0,
                'unit' => 'kg',
                'measured_at' => now()->format('Y-m-d'),
            ]);

        $response->assertStatus(403);
    }

    public function test_validation_fails_for_invalid_weight(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->vetToken)
            ->postJson("/api/v1/pets/{$this->pet->id}/weight", [
                'weight' => -5,
                'unit' => 'kg',
                'measured_at' => now()->format('Y-m-d'),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['weight']);
    }
}
