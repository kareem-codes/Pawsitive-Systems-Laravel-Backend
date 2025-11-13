<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Pet;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $cashier;
    protected Pet $pet;
    protected Product $product;
    protected string $ownerToken;
    protected string $cashierToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $this->owner = User::factory()->owner()->create();
        $this->owner->assignRole('owner');
        $this->ownerToken = $this->owner->createToken('test-token')->plainTextToken;

        $this->cashier = User::factory()->cashier()->create();
        $this->cashier->assignRole('cashier');
        $this->cashierToken = $this->cashier->createToken('test-token')->plainTextToken;

        $this->pet = Pet::factory()->create(['user_id' => $this->owner->id]);
        $this->product = Product::factory()->create(['price' => 100.00]);
    }

    public function test_cashier_can_create_invoice(): void
    {
        $invoiceData = [
            'invoice_number' => 'INV-TEST-001',
            'user_id' => $this->owner->id,
            'pet_id' => $this->pet->id,
            'created_by' => $this->cashier->id,
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'discount_amount' => 5.00,
            'notes' => 'Test invoice',
            'items' => [
                [
                    'type' => 'product',
                    'item_name' => 'Test Product',
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_price' => 100.00,
                ],
            ],
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->cashierToken)
            ->postJson('/api/v1/invoices', $invoiceData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'invoice' => [
                    'id',
                    'invoice_number',
                    'subtotal',
                    'tax_amount',
                    'discount_amount',
                    'total_amount',
                    'items',
                ]
            ]);

        $this->assertDatabaseHas('invoices', [
            'user_id' => $this->owner->id,
            'pet_id' => $this->pet->id,
        ]);

        $this->assertDatabaseHas('invoice_items', [
            'product_id' => $this->product->id,
            'quantity' => 2,
        ]);
    }

    public function test_invoice_calculates_totals_correctly(): void
    {
        $invoiceData = [
            'invoice_number' => 'INV-TEST-002',
            'user_id' => $this->owner->id,
            'pet_id' => $this->pet->id,
            'created_by' => $this->cashier->id,
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'items' => [
                [
                    'type' => 'product',
                    'item_name' => 'Test Product',
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_price' => 100.00,
                    'tax_percentage' => 10,
                    'discount_amount' => 10.00,
                ],
            ],
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->cashierToken)
            ->postJson('/api/v1/invoices', $invoiceData);

        $response->assertStatus(201);

        $subtotal = 200.00;
        $discount = 10.00;
        $tax = ($subtotal * 10) / 100; // Tax is calculated on subtotal, not after discount
        $total = $subtotal + $tax - $discount;

        $response->assertJson([
            'invoice' => [
                'subtotal' => number_format($subtotal, 2, '.', ''),
                'tax_amount' => number_format($tax, 2, '.', ''),
                'discount_amount' => number_format($discount, 2, '.', ''),
                'total_amount' => number_format($total, 2, '.', ''),
            ]
        ]);
    }

    public function test_owner_can_view_their_invoices(): void
    {
        Invoice::factory()->count(3)->create(['user_id' => $this->owner->id]);
        Invoice::factory()->count(2)->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->getJson('/api/v1/invoices');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_owner_can_view_their_invoice_details(): void
    {
        $invoice = Invoice::factory()->create(['user_id' => $this->owner->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->getJson("/api/v1/invoices/{$invoice->id}");

        $response->assertStatus(200)
            ->assertJson(['id' => $invoice->id]);
    }

    public function test_owner_cannot_view_other_owner_invoice(): void
    {
        $otherInvoice = Invoice::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->getJson("/api/v1/invoices/{$otherInvoice->id}");

        $response->assertStatus(403);
    }

    public function test_filter_invoices_by_status(): void
    {
        Invoice::factory()->create([
            'user_id' => $this->owner->id,
            'status' => 'paid',
        ]);
        Invoice::factory()->create([
            'user_id' => $this->owner->id,
            'status' => 'pending',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->getJson('/api/v1/invoices?status=paid');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'paid');
    }

    public function test_get_unpaid_invoices(): void
    {
        Invoice::factory()->create([
            'user_id' => $this->owner->id,
            'status' => 'pending',
        ]);
        Invoice::factory()->create([
            'user_id' => $this->owner->id,
            'status' => 'paid',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->getJson('/api/v1/invoices?status=pending');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_cashier_can_update_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'user_id' => $this->owner->id,
            'pet_id' => $this->pet->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->cashierToken)
            ->putJson("/api/v1/invoices/{$invoice->id}", [
                'user_id' => $this->owner->id,
                'tax_percentage' => 15,
                'notes' => 'Updated notes',
            ]);

        $response->assertStatus(200)
            ->assertJson(['invoice' => ['notes' => 'Updated notes']]);
    }

    public function test_cashier_can_delete_invoice(): void
    {
        $invoice = Invoice::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->cashierToken)
            ->deleteJson("/api/v1/invoices/{$invoice->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
    }

    public function test_validation_fails_for_empty_items(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->cashierToken)
            ->postJson('/api/v1/invoices', [
                'user_id' => $this->owner->id,
                'items' => [],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }
}
