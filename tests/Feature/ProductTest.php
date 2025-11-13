<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $cashier;
    protected User $owner;
    protected string $adminToken;
    protected string $cashierToken;
    protected string $ownerToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $this->admin = User::factory()->admin()->create();
        $this->admin->assignRole('admin');
        $this->adminToken = $this->admin->createToken('test-token')->plainTextToken;

        $this->cashier = User::factory()->cashier()->create();
        $this->cashier->assignRole('cashier');
        $this->cashierToken = $this->cashier->createToken('test-token')->plainTextToken;

        $this->owner = User::factory()->owner()->create();
        $this->owner->assignRole('owner');
        $this->ownerToken = $this->owner->createToken('test-token')->plainTextToken;
    }

    public function test_admin_can_create_product(): void
    {
        $productData = [
            'name' => 'Premium Dog Food',
            'sku' => 'PDF-001',
            'category' => 'food',
            'price' => 49.99,
            'cost' => 30.00,
            'quantity_in_stock' => 100,
            'reorder_level' => 20,
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson('/api/v1/products', $productData);

        $response->assertStatus(201)
            ->assertJson([
                'name' => 'Premium Dog Food',
                'sku' => 'PDF-001',
            ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Premium Dog Food',
            'sku' => 'PDF-001',
        ]);
    }

    public function test_owner_can_view_products(): void
    {
        Product::factory()->count(5)->create(['is_active' => true]);
        Product::factory()->count(2)->create(['is_active' => false]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->getJson('/api/v1/products');

        $response->assertStatus(200);
    }

    public function test_search_products_by_name(): void
    {
        Product::factory()->create(['name' => 'Dog Food Premium']);
        Product::factory()->create(['name' => 'Cat Food Standard']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->getJson('/api/v1/products?search=Dog');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Dog Food Premium');
    }

    public function test_filter_products_by_category(): void
    {
        Product::factory()->create(['category' => 'food']);
        Product::factory()->create(['category' => 'medicine']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->getJson('/api/v1/products?category=food');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.category', 'food');
    }

    public function test_get_low_stock_products(): void
    {
        Product::factory()->create([
            'quantity_in_stock' => 5,
            'reorder_threshold' => 10,
        ]);
        Product::factory()->create([
            'quantity_in_stock' => 50,
            'reorder_threshold' => 10,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/v1/products?low_stock=true');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_can_update_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->putJson("/api/v1/products/{$product->id}", [
                'name' => 'Updated Product Name',
                'sku' => $product->sku,
                'category' => $product->category,
                'price' => 59.99,
                'quantity_in_stock' => $product->quantity_in_stock,
            ]);

        $response->assertStatus(200)
            ->assertJson(['product' => ['name' => 'Updated Product Name']]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product Name',
        ]);
    }

    public function test_admin_can_delete_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->deleteJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_sku_must_be_unique(): void
    {
        Product::factory()->create(['sku' => 'UNIQUE-001']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson('/api/v1/products', [
                'name' => 'Another Product',
                'sku' => 'UNIQUE-001',
                'category' => 'food',
                'price' => 29.99,
                'quantity_in_stock' => 10,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sku']);
    }

    public function test_validation_fails_for_negative_price(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson('/api/v1/products', [
                'name' => 'Test Product',
                'sku' => 'TEST-001',
                'category' => 'food',
                'price' => -10.00,
                'quantity_in_stock' => 5,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price']);
    }
}
