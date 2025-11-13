<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\CommunicationLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunicationLogTest extends TestCase
{
    use RefreshDatabase;

    protected User $receptionist;
    protected User $owner;
    protected string $receptionistToken;
    protected string $ownerToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $this->receptionist = User::factory()->receptionist()->create();
        $this->receptionist->assignRole('receptionist');
        $this->receptionistToken = $this->receptionist->createToken('test-token')->plainTextToken;

        $this->owner = User::factory()->owner()->create();
        $this->owner->assignRole('owner');
        $this->ownerToken = $this->owner->createToken('test-token')->plainTextToken;
    }

    public function test_can_create_communication_log(): void
    {
        $logData = [
            'user_id' => $this->owner->id,
            'type' => 'call',
            'direction' => 'outbound',
            'subject' => 'Appointment reminder',
            'notes' => 'Called to remind about upcoming appointment',
            'contacted_at' => now()->format('Y-m-d H:i:s'),
            'duration_minutes' => 5,
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->receptionistToken)
            ->postJson('/api/v1/communication-logs', $logData);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Communication log created successfully',
                'log' => [
                    'type' => 'call',
                    'direction' => 'outbound',
                ]
            ]);

        $this->assertDatabaseHas('communication_logs', [
            'user_id' => $this->owner->id,
            'type' => 'call',
            'staff_id' => $this->receptionist->id,
        ]);
    }

    public function test_can_list_communication_logs(): void
    {
        CommunicationLog::factory()->count(5)->create([
            'user_id' => $this->owner->id,
            'staff_id' => $this->receptionist->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->receptionistToken)
            ->getJson('/api/v1/communication-logs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'type', 'direction', 'contacted_at']
                ]
            ]);
    }

    public function test_can_filter_logs_by_type(): void
    {
        CommunicationLog::factory()->create([
            'user_id' => $this->owner->id,
            'type' => 'call',
            'staff_id' => $this->receptionist->id,
        ]);

        CommunicationLog::factory()->create([
            'user_id' => $this->owner->id,
            'type' => 'email',
            'staff_id' => $this->receptionist->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->receptionistToken)
            ->getJson('/api/v1/communication-logs?type=call');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'call');
    }

    public function test_can_update_communication_log(): void
    {
        $log = CommunicationLog::factory()->create([
            'user_id' => $this->owner->id,
            'staff_id' => $this->receptionist->id,
            'notes' => 'Initial notes',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->receptionistToken)
            ->putJson("/api/v1/communication-logs/{$log->id}", [
                'notes' => 'Updated notes',
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Communication log updated successfully']);

        $this->assertDatabaseHas('communication_logs', [
            'id' => $log->id,
            'notes' => 'Updated notes',
        ]);
    }

    public function test_can_delete_communication_log(): void
    {
        $log = CommunicationLog::factory()->create([
            'user_id' => $this->owner->id,
            'staff_id' => $this->receptionist->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->receptionistToken)
            ->deleteJson("/api/v1/communication-logs/{$log->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Communication log deleted successfully']);

        $this->assertDatabaseMissing('communication_logs', ['id' => $log->id]);
    }

    public function test_can_get_communication_statistics(): void
    {
        CommunicationLog::factory()->count(3)->create([
            'user_id' => $this->owner->id,
            'type' => 'call',
            'staff_id' => $this->receptionist->id,
            'duration_minutes' => 10,
        ]);

        CommunicationLog::factory()->count(2)->create([
            'user_id' => $this->owner->id,
            'type' => 'email',
            'staff_id' => $this->receptionist->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->receptionistToken)
            ->getJson("/api/v1/communication-logs/statistics?user_id={$this->owner->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_communications',
                'by_type',
                'by_direction',
                'total_call_duration',
                'last_contact',
            ])
            ->assertJson([
                'total_communications' => 5,
            ]);
    }

    public function test_validation_fails_for_invalid_type(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->receptionistToken)
            ->postJson('/api/v1/communication-logs', [
                'user_id' => $this->owner->id,
                'type' => 'invalid_type',
                'direction' => 'outbound',
                'contacted_at' => now()->format('Y-m-d H:i:s'),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }
}
