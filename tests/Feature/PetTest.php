<?php

namespace Tests\Feature;

use App\Models\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PetTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $admin;
    protected string $ownerToken;
    protected string $adminToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $this->owner = User::factory()->owner()->create();
        $this->owner->assignRole('owner');
        $this->ownerToken = $this->owner->createToken('test-token')->plainTextToken;

        $this->admin = User::factory()->admin()->create();
        $this->admin->assignRole('admin');
        $this->adminToken = $this->admin->createToken('test-token')->plainTextToken;
    }

    public function test_owner_can_view_their_pets(): void
    {
        Pet::factory()->count(3)->create(['user_id' => $this->owner->id]);
        Pet::factory()->count(2)->create(); // Other owner's pets

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->getJson('/api/v1/pets');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_view_all_pets(): void
    {
        Pet::factory()->count(3)->create(['user_id' => $this->owner->id]);
        Pet::factory()->count(2)->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/v1/pets');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    public function test_owner_can_create_pet(): void
    {
        $petData = [
            'name' => 'Fluffy',
            'species' => 'cat',
            'breed' => 'Persian',
            'birth_date' => '2020-05-15',
            'gender' => 'female',
            'color' => 'white',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->postJson('/api/v1/pets', $petData);

        $response->assertStatus(201)
            ->assertJson([
                'name' => 'Fluffy',
                'species' => 'cat',
            ]);

        $this->assertDatabaseHas('pets', [
            'name' => 'Fluffy',
            'user_id' => $this->owner->id,
        ]);
    }

    public function test_owner_can_view_their_pet(): void
    {
        $pet = Pet::factory()->create(['user_id' => $this->owner->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->getJson("/api/v1/pets/{$pet->id}");

        $response->assertStatus(200)
            ->assertJson(['id' => $pet->id]);
    }

    public function test_owner_cannot_view_other_owner_pet(): void
    {
        $otherPet = Pet::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->getJson("/api/v1/pets/{$otherPet->id}");

        $response->assertStatus(403);
    }

    public function test_owner_can_update_their_pet(): void
    {
        $pet = Pet::factory()->create(['user_id' => $this->owner->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->putJson("/api/v1/pets/{$pet->id}", [
                'name' => 'Updated Name',
                'species' => $pet->species,
                'breed' => $pet->breed,
                'birth_date' => $pet->birth_date,
                'gender' => $pet->gender,
            ]);

        $response->assertStatus(200)
            ->assertJson(['pet' => ['name' => 'Updated Name']]);

        $this->assertDatabaseHas('pets', [
            'id' => $pet->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_owner_cannot_delete_their_pet(): void
    {
        $pet = Pet::factory()->create(['user_id' => $this->owner->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->deleteJson("/api/v1/pets/{$pet->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('pets', ['id' => $pet->id, 'deleted_at' => null]);
    }

    public function test_search_pets_by_name(): void
    {
        Pet::factory()->create(['name' => 'Fluffy', 'user_id' => $this->owner->id]);
        Pet::factory()->create(['name' => 'Max', 'user_id' => $this->owner->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->getJson('/api/v1/pets?search=Fluffy');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Fluffy');
    }

    public function test_filter_pets_by_species(): void
    {
        Pet::factory()->create(['species' => 'dog', 'user_id' => $this->owner->id]);
        Pet::factory()->create(['species' => 'cat', 'user_id' => $this->owner->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->getJson('/api/v1/pets?species=dog');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.species', 'dog');
    }

    public function test_validation_fails_for_invalid_pet_data(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->postJson('/api/v1/pets', [
                'name' => '',
                'species' => 'invalid',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'species', 'breed', 'birth_date', 'gender']);
    }
}
