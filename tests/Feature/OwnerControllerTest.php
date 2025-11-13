<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Pet;
use App\Models\Appointment;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $receptionist;
    protected User $owner;
    protected string $adminToken;
    protected string $receptionistToken;
    protected string $ownerToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $this->admin = User::factory()->admin()->create();
        $this->admin->assignRole('admin');
        $this->adminToken = $this->admin->createToken('test-token')->plainTextToken;

        $this->receptionist = User::factory()->receptionist()->create();
        $this->receptionist->assignRole('receptionist');
        $this->receptionistToken = $this->receptionist->createToken('test-token')->plainTextToken;

        $this->owner = User::factory()->owner()->create();
        $this->owner->assignRole('owner');
        $this->ownerToken = $this->owner->createToken('test-token')->plainTextToken;
    }

    public function test_receptionist_can_list_owners(): void
    {
        User::factory()->count(5)->owner()->create()->each(function ($user) {
            $user->assignRole('owner');
        });

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->receptionistToken)
            ->getJson('/api/v1/owners');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'first_name', 'last_name', 'email', 'phone']
                ]
            ]);
    }

    public function test_can_search_owners_by_name(): void
    {
        $owner1 = User::factory()->owner()->create(['first_name' => 'John', 'last_name' => 'Doe']);
        $owner1->assignRole('owner');

        $owner2 = User::factory()->owner()->create(['first_name' => 'Jane', 'last_name' => 'Smith']);
        $owner2->assignRole('owner');

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->receptionistToken)
            ->getJson('/api/v1/owners?search=John');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.first_name', 'John');
    }

    public function test_receptionist_can_create_owner(): void
    {
        $ownerData = [
            'first_name' => 'Test',
            'last_name' => 'Owner',
            'email' => 'testowner@example.com',
            'password' => 'password123',
            'phone' => '123-456-7890',
            'phone_secondary' => '098-765-4321',
            'emergency_contact_name' => 'Emergency Contact',
            'emergency_contact_phone' => '111-222-3333',
            'address' => '123 Main St',
            'city' => 'Springfield',
            'state' => 'IL',
            'postal_code' => '62701',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->receptionistToken)
            ->postJson('/api/v1/owners', $ownerData);

        $response->assertStatus(201)
            ->assertJson([
                'owner' => [
                    'first_name' => 'Test',
                    'last_name' => 'Owner',
                    'email' => 'testowner@example.com',
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'testowner@example.com',
            'first_name' => 'Test',
        ]);

        $owner = User::where('email', 'testowner@example.com')->first();
        $this->assertTrue($owner->hasRole('owner'));
    }

    public function test_can_view_owner_details(): void
    {
        Pet::factory()->count(2)->create(['user_id' => $this->owner->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->receptionistToken)
            ->getJson("/api/v1/owners/{$this->owner->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'first_name',
                'last_name',
                'email',
                'phone',
                'pets' => [
                    '*' => ['id', 'name', 'species']
                ]
            ]);
    }

    public function test_can_update_owner_information(): void
    {
        $updateData = [
            'first_name' => 'Updated',
            'phone' => '999-888-7777',
            'city' => 'New City',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->receptionistToken)
            ->putJson("/api/v1/owners/{$this->owner->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'owner' => [
                    'first_name' => 'Updated',
                    'phone' => '999-888-7777',
                    'city' => 'New City',
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->owner->id,
            'first_name' => 'Updated',
        ]);
    }

    public function test_can_update_emergency_contact(): void
    {
        $emergencyData = [
            'emergency_contact_name' => 'New Emergency Contact',
            'emergency_contact_phone' => '555-123-4567',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->receptionistToken)
            ->putJson("/api/v1/owners/{$this->owner->id}/emergency-contact", $emergencyData);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Emergency contact updated successfully',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->owner->id,
            'emergency_contact_name' => 'New Emergency Contact',
            'emergency_contact_phone' => '555-123-4567',
        ]);
    }

    public function test_cannot_delete_owner_with_pets(): void
    {
        Pet::factory()->create(['user_id' => $this->owner->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->deleteJson("/api/v1/owners/{$this->owner->id}");

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Cannot delete owner with existing pets. Please remove or reassign pets first.',
            ]);
    }

    public function test_can_delete_owner_without_pets(): void
    {
        $ownerWithoutPets = User::factory()->owner()->create();
        $ownerWithoutPets->assignRole('owner');

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->deleteJson("/api/v1/owners/{$ownerWithoutPets->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Pet owner deleted successfully',
            ]);

        $this->assertSoftDeleted('users', ['id' => $ownerWithoutPets->id]);
    }

    public function test_can_get_owner_statistics(): void
    {
        // Create pets
        $pets = Pet::factory()->count(2)->create(['user_id' => $this->owner->id]);

        // Create appointments
        $vet = User::factory()->veterinarian()->create();
        Appointment::factory()->count(3)->create([
            'user_id' => $this->owner->id,
            'veterinarian_id' => $vet->id,
            'pet_id' => $pets->first()->id,
            'status' => 'completed',
        ]);
        Appointment::factory()->count(2)->create([
            'user_id' => $this->owner->id,
            'veterinarian_id' => $vet->id,
            'pet_id' => $pets->first()->id,
            'status' => 'pending',
            'appointment_date' => now()->addDays(5),
        ]);

        // Create invoices
        Invoice::factory()->count(2)->create([
            'user_id' => $this->owner->id,
            'status' => 'paid',
            'total_amount' => 100.00,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->receptionistToken)
            ->getJson("/api/v1/owners/{$this->owner->id}/statistics");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_pets',
                'active_pets',
                'total_appointments',
                'upcoming_appointments',
                'completed_appointments',
                'total_invoices',
                'pending_invoices',
                'total_spent',
            ])
            ->assertJson([
                'total_pets' => 2,
                'total_appointments' => 5,
                'upcoming_appointments' => 2,
                'completed_appointments' => 3,
                'total_invoices' => 2,
                'total_spent' => 200.00,
            ]);
    }

    public function test_owner_can_view_own_profile(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->getJson("/api/v1/owners/{$this->owner->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $this->owner->id,
                'email' => $this->owner->email,
            ]);
    }

    public function test_validation_fails_for_duplicate_email(): void
    {
        $existingOwner = User::factory()->owner()->create(['email' => 'existing@example.com']);
        $existingOwner->assignRole('owner');

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->receptionistToken)
            ->postJson('/api/v1/owners', [
                'first_name' => 'Test',
                'last_name' => 'Owner',
                'email' => 'existing@example.com',
                'password' => 'password123',
                'phone' => '123-456-7890',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_filter_owners_by_city(): void
    {
        User::factory()->owner()->create(['city' => 'Springfield'])->assignRole('owner');
        User::factory()->owner()->create(['city' => 'Shelbyville'])->assignRole('owner');

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->receptionistToken)
            ->getJson('/api/v1/owners?city=Springfield');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.city', 'Springfield');
    }
}
