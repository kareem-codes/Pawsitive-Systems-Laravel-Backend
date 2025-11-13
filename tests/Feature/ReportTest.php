<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Pet;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $staff;
    protected $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->staff = User::factory()->create();
        $this->staff->assignRole('staff');

        $this->owner = User::factory()->create();
        $this->owner->assignRole('owner');
    }

    public function test_can_generate_revenue_report()
    {
        $invoice = Invoice::factory()->create([
            'owner_id' => $this->owner->id,
            'total' => 150.00,
            'status' => 'paid',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/reports/revenue?start_date=' . now()->subMonth()->format('Y-m-d') . '&end_date=' . now()->format('Y-m-d'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'total_revenue',
                    'paid_invoices',
                    'pending_invoices',
                    'average_invoice',
                ],
            ]);

        $this->assertEquals(150.00, $response->json('data.total_revenue'));
    }

    public function test_can_generate_appointments_report()
    {
        $pet = Pet::factory()->create([
            'owner_id' => $this->owner->id,
        ]);

        Appointment::factory()->create([
            'pet_id' => $pet->id,
            'status' => 'completed',
            'created_at' => now(),
        ]);

        Appointment::factory()->create([
            'pet_id' => $pet->id,
            'status' => 'cancelled',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/reports/appointments?start_date=' . now()->subMonth()->format('Y-m-d') . '&end_date=' . now()->format('Y-m-d'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'total_appointments',
                    'completed',
                    'cancelled',
                    'no_show',
                    'pending',
                ],
            ]);

        $this->assertEquals(2, $response->json('data.total_appointments'));
        $this->assertEquals(1, $response->json('data.completed'));
        $this->assertEquals(1, $response->json('data.cancelled'));
    }

    public function test_can_generate_services_report()
    {
        $invoice = Invoice::factory()->create([
            'owner_id' => $this->owner->id,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'type' => 'service',
            'item_name' => 'Vaccination',
            'quantity' => 2,
            'unit_price' => 50.00,
            'total' => 100.00,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'type' => 'service',
            'item_name' => 'Vaccination',
            'quantity' => 1,
            'unit_price' => 50.00,
            'total' => 50.00,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/reports/services?start_date=' . now()->subMonth()->format('Y-m-d') . '&end_date=' . now()->format('Y-m-d'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'service_name',
                        'times_provided',
                        'total_revenue',
                    ],
                ],
            ]);

        $this->assertEquals(3, $response->json('data.0.times_provided'));
        $this->assertEquals(150.00, $response->json('data.0.total_revenue'));
    }

    public function test_can_generate_product_sales_report()
    {
        $product = Product::factory()->create([
            'name' => 'Dog Food',
            'price' => 30.00,
        ]);

        $invoice = Invoice::factory()->create([
            'owner_id' => $this->owner->id,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'price' => 30.00,
            'total' => 90.00,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/reports/product-sales?start_date=' . now()->subMonth()->format('Y-m-d') . '&end_date=' . now()->format('Y-m-d'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'product_id',
                        'product_name',
                        'quantity_sold',
                        'total_revenue',
                    ],
                ],
            ]);

        $this->assertEquals(3, $response->json('data.0.quantity_sold'));
        $this->assertEquals(90.00, $response->json('data.0.total_revenue'));
    }

    public function test_can_generate_client_retention_report()
    {
        $pet = Pet::factory()->create([
            'owner_id' => $this->owner->id,
        ]);

        // Create appointments in different months
        Appointment::factory()->create([
            'pet_id' => $pet->id,
            'status' => 'completed',
            'created_at' => now()->subMonths(2),
        ]);

        Appointment::factory()->create([
            'pet_id' => $pet->id,
            'status' => 'completed',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/reports/client-retention?start_date=' . now()->subYear()->format('Y-m-d') . '&end_date=' . now()->format('Y-m-d'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'total_clients',
                    'active_clients',
                    'new_clients',
                    'returning_clients',
                    'retention_rate',
                ],
            ]);
    }

    public function test_revenue_report_filters_by_date_range()
    {
        $oldInvoice = Invoice::factory()->create([
            'owner_id' => $this->owner->id,
            'total' => 100.00,
            'status' => 'paid',
            'created_at' => now()->subMonths(6),
        ]);

        $recentInvoice = Invoice::factory()->create([
            'owner_id' => $this->owner->id,
            'total' => 200.00,
            'status' => 'paid',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/reports/revenue?start_date=' . now()->subMonth()->format('Y-m-d') . '&end_date=' . now()->format('Y-m-d'));

        $response->assertOk();
        $this->assertEquals(200.00, $response->json('data.total_revenue'));
    }

    public function test_staff_can_view_reports()
    {
        $response = $this->actingAs($this->staff)
            ->getJson('/api/reports/revenue?start_date=' . now()->subMonth()->format('Y-m-d') . '&end_date=' . now()->format('Y-m-d'));

        $response->assertOk();
    }

    public function test_owner_cannot_view_reports()
    {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/reports/revenue?start_date=' . now()->subMonth()->format('Y-m-d') . '&end_date=' . now()->format('Y-m-d'));

        $response->assertForbidden();
    }

    public function test_reports_require_date_range()
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/reports/revenue');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date', 'end_date']);
    }

    public function test_reports_validate_date_format()
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/reports/revenue?start_date=invalid&end_date=invalid');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date', 'end_date']);
    }

    public function test_revenue_report_excludes_cancelled_invoices()
    {
        Invoice::factory()->create([
            'owner_id' => $this->owner->id,
            'total' => 100.00,
            'status' => 'paid',
            'created_at' => now(),
        ]);

        Invoice::factory()->create([
            'owner_id' => $this->owner->id,
            'total' => 50.00,
            'status' => 'cancelled',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/reports/revenue?start_date=' . now()->subMonth()->format('Y-m-d') . '&end_date=' . now()->format('Y-m-d'));

        $response->assertOk();
        $this->assertEquals(100.00, $response->json('data.total_revenue'));
    }

    public function test_appointments_report_groups_by_status()
    {
        $pet = Pet::factory()->create([
            'owner_id' => $this->owner->id,
        ]);

        Appointment::factory()->count(3)->create([
            'pet_id' => $pet->id,
            'status' => 'completed',
            'created_at' => now(),
        ]);

        Appointment::factory()->count(2)->create([
            'pet_id' => $pet->id,
            'status' => 'pending',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/reports/appointments?start_date=' . now()->subMonth()->format('Y-m-d') . '&end_date=' . now()->format('Y-m-d'));

        $response->assertOk();
        $this->assertEquals(3, $response->json('data.completed'));
        $this->assertEquals(2, $response->json('data.pending'));
    }
}
