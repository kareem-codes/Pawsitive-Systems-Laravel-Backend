<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Pet;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Product;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    protected $staff;
    protected $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->staff = User::factory()->create();
        $this->staff->assignRole('staff');

        $this->owner = User::factory()->create([
            'name' => 'John Smith',
            'email' => 'john.smith@example.com',
        ]);
        $this->owner->assignRole('owner');
    }

    public function test_can_search_across_all_entities()
    {
        $pet = Pet::factory()->create([
            'name' => 'Fluffy',
            'owner_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->staff)
            ->getJson('/api/search?query=Fluffy');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'pets',
                    'owners',
                    'appointments',
                    'invoices',
                    'products',
                ],
            ]);
    }

    public function test_can_search_pets()
    {
        Pet::factory()->create([
            'name' => 'Max',
            'owner_id' => $this->owner->id,
        ]);

        Pet::factory()->create([
            'name' => 'Bella',
            'owner_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->staff)
            ->getJson('/api/search?query=Max');

        $response->assertOk();

        $pets = $response->json('data.pets');
        $this->assertCount(1, $pets);
        $this->assertEquals('Max', $pets[0]['name']);
    }

    public function test_can_search_owners()
    {
        $response = $this->actingAs($this->staff)
            ->getJson('/api/search?query=John');

        $response->assertOk();

        $owners = $response->json('data.owners');
        $this->assertGreaterThan(0, count($owners));
        $this->assertStringContainsString('John', $owners[0]['name']);
    }

    public function test_can_search_by_email()
    {
        $response = $this->actingAs($this->staff)
            ->getJson('/api/search?query=john.smith@example.com');

        $response->assertOk();

        $owners = $response->json('data.owners');
        $this->assertGreaterThan(0, count($owners));
        $this->assertEquals('john.smith@example.com', $owners[0]['email']);
    }

    public function test_can_search_appointments()
    {
        $pet = Pet::factory()->create([
            'name' => 'Charlie',
            'owner_id' => $this->owner->id,
        ]);

        $appointment = Appointment::factory()->create([
            'pet_id' => $pet->id,
            'reason' => 'Annual checkup',
        ]);

        $response = $this->actingAs($this->staff)
            ->getJson('/api/search?query=checkup');

        $response->assertOk();

        $appointments = $response->json('data.appointments');
        $this->assertGreaterThan(0, count($appointments));
    }

    public function test_can_search_invoices()
    {
        $invoice = Invoice::factory()->create([
            'invoice_number' => 'INV-2024-001',
            'owner_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->staff)
            ->getJson('/api/search?query=INV-2024-001');

        $response->assertOk();

        $invoices = $response->json('data.invoices');
        $this->assertGreaterThan(0, count($invoices));
        $this->assertEquals('INV-2024-001', $invoices[0]['invoice_number']);
    }

    public function test_can_search_products()
    {
        Product::factory()->create([
            'name' => 'Flea Treatment',
            'sku' => 'FT-001',
        ]);

        $response = $this->actingAs($this->staff)
            ->getJson('/api/search?query=Flea');

        $response->assertOk();

        $products = $response->json('data.products');
        $this->assertGreaterThan(0, count($products));
        $this->assertStringContainsString('Flea', $products[0]['name']);
    }

    public function test_can_search_by_sku()
    {
        Product::factory()->create([
            'name' => 'Dog Food',
            'sku' => 'DF-123',
        ]);

        $response = $this->actingAs($this->staff)
            ->getJson('/api/search?query=DF-123');

        $response->assertOk();

        $products = $response->json('data.products');
        $this->assertGreaterThan(0, count($products));
        $this->assertEquals('DF-123', $products[0]['sku']);
    }

    public function test_empty_search_returns_empty_results()
    {
        $response = $this->actingAs($this->staff)
            ->getJson('/api/search?query=');

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'pets' => [],
                    'owners' => [],
                    'appointments' => [],
                    'invoices' => [],
                    'products' => [],
                ],
            ]);
    }

    public function test_search_is_case_insensitive()
    {
        Pet::factory()->create([
            'name' => 'Rocky',
            'owner_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->staff)
            ->getJson('/api/search?query=rocky');

        $response->assertOk();

        $pets = $response->json('data.pets');
        $this->assertCount(1, $pets);
        $this->assertEquals('Rocky', $pets[0]['name']);
    }

    public function test_owner_can_only_search_own_data()
    {
        $otherOwner = User::factory()->create();
        $otherOwner->assignRole('owner');

        $myPet = Pet::factory()->create([
            'name' => 'MyPet',
            'owner_id' => $this->owner->id,
        ]);

        $otherPet = Pet::factory()->create([
            'name' => 'OtherPet',
            'owner_id' => $otherOwner->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson('/api/search?query=Pet');

        $response->assertOk();

        $pets = $response->json('data.pets');
        $this->assertEquals(1, count($pets));
        $this->assertEquals('MyPet', $pets[0]['name']);
    }

    public function test_search_requires_authentication()
    {
        $response = $this->getJson('/api/search?query=test');

        $response->assertUnauthorized();
    }
}
