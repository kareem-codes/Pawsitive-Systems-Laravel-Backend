<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Pet;
use App\Models\AuditLog;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuditLogTest extends TestCase
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

    public function test_can_list_audit_logs()
    {
        AuditLog::create([
            'user_id' => $this->staff->id,
            'action' => 'created',
            'model_type' => 'Pet',
            'model_id' => 1,
            'description' => 'Created pet record',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/audit-logs');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'action',
                        'model_type',
                        'model_id',
                        'description',
                        'created_at',
                    ],
                ],
            ]);
    }

    public function test_can_filter_logs_by_user()
    {
        AuditLog::create([
            'user_id' => $this->staff->id,
            'action' => 'updated',
            'model_type' => 'Pet',
            'model_id' => 1,
        ]);

        AuditLog::create([
            'user_id' => $this->admin->id,
            'action' => 'deleted',
            'model_type' => 'Pet',
            'model_id' => 2,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/audit-logs?user_id={$this->staff->id}");

        $response->assertOk();

        $logs = $response->json('data');
        $this->assertCount(1, $logs);
        $this->assertEquals($this->staff->id, $logs[0]['user_id']);
    }

    public function test_can_filter_logs_by_action()
    {
        AuditLog::create([
            'user_id' => $this->staff->id,
            'action' => 'created',
            'model_type' => 'Pet',
            'model_id' => 1,
        ]);

        AuditLog::create([
            'user_id' => $this->staff->id,
            'action' => 'updated',
            'model_type' => 'Pet',
            'model_id' => 1,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/audit-logs?action=created');

        $response->assertOk();

        $logs = $response->json('data');
        $this->assertCount(1, $logs);
        $this->assertEquals('created', $logs[0]['action']);
    }

    public function test_can_filter_logs_by_model_type()
    {
        AuditLog::create([
            'user_id' => $this->staff->id,
            'action' => 'created',
            'model_type' => 'Pet',
            'model_id' => 1,
        ]);

        AuditLog::create([
            'user_id' => $this->staff->id,
            'action' => 'created',
            'model_type' => 'Appointment',
            'model_id' => 1,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/audit-logs?model_type=Pet');

        $response->assertOk();

        $logs = $response->json('data');
        $this->assertCount(1, $logs);
        $this->assertEquals('Pet', $logs[0]['model_type']);
    }

    public function test_can_filter_logs_by_date_range()
    {
        $oldLog = AuditLog::create([
            'user_id' => $this->staff->id,
            'action' => 'created',
            'model_type' => 'Pet',
            'model_id' => 1,
            'created_at' => now()->subDays(10),
        ]);

        $recentLog = AuditLog::create([
            'user_id' => $this->staff->id,
            'action' => 'updated',
            'model_type' => 'Pet',
            'model_id' => 1,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/audit-logs?start_date=' . now()->subDays(5)->format('Y-m-d'));

        $response->assertOk();

        $logs = $response->json('data');
        $this->assertCount(1, $logs);
        $this->assertEquals($recentLog->id, $logs[0]['id']);
    }

    public function test_can_get_logs_for_specific_model()
    {
        $pet = Pet::factory()->create([
            'owner_id' => $this->owner->id,
        ]);

        AuditLog::create([
            'user_id' => $this->staff->id,
            'action' => 'created',
            'model_type' => 'Pet',
            'model_id' => $pet->id,
        ]);

        AuditLog::create([
            'user_id' => $this->staff->id,
            'action' => 'updated',
            'model_type' => 'Pet',
            'model_id' => $pet->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/audit-logs/model/Pet/{$pet->id}");

        $response->assertOk();

        $logs = $response->json('data');
        $this->assertCount(2, $logs);
        $this->assertEquals($pet->id, $logs[0]['model_id']);
    }

    public function test_audit_logs_include_user_information()
    {
        AuditLog::create([
            'user_id' => $this->staff->id,
            'action' => 'created',
            'model_type' => 'Pet',
            'model_id' => 1,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/audit-logs');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'user' => [
                            'id',
                            'name',
                            'email',
                        ],
                    ],
                ],
            ]);
    }

    public function test_staff_can_view_audit_logs()
    {
        AuditLog::create([
            'user_id' => $this->staff->id,
            'action' => 'created',
            'model_type' => 'Pet',
            'model_id' => 1,
        ]);

        $response = $this->actingAs($this->staff)
            ->getJson('/api/audit-logs');

        $response->assertOk();
    }

    public function test_owner_cannot_view_audit_logs()
    {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/audit-logs');

        $response->assertForbidden();
    }

    public function test_can_search_audit_logs_by_description()
    {
        AuditLog::create([
            'user_id' => $this->staff->id,
            'action' => 'created',
            'model_type' => 'Pet',
            'model_id' => 1,
            'description' => 'Created new pet named Fluffy',
        ]);

        AuditLog::create([
            'user_id' => $this->staff->id,
            'action' => 'updated',
            'model_type' => 'Pet',
            'model_id' => 1,
            'description' => 'Updated pet weight',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/audit-logs?search=Fluffy');

        $response->assertOk();

        $logs = $response->json('data');
        $this->assertCount(1, $logs);
        $this->assertStringContainsString('Fluffy', $logs[0]['description']);
    }

    public function test_audit_logs_are_ordered_by_latest_first()
    {
        $firstLog = AuditLog::create([
            'user_id' => $this->staff->id,
            'action' => 'created',
            'model_type' => 'Pet',
            'model_id' => 1,
            'created_at' => now()->subHours(2),
        ]);

        $secondLog = AuditLog::create([
            'user_id' => $this->staff->id,
            'action' => 'updated',
            'model_type' => 'Pet',
            'model_id' => 1,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/audit-logs');

        $response->assertOk();

        $logs = $response->json('data');
        $this->assertEquals($secondLog->id, $logs[0]['id']);
        $this->assertEquals($firstLog->id, $logs[1]['id']);
    }
}
