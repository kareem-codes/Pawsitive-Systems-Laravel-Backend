<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Pet;
use App\Models\AuditLog;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ObserverTest extends TestCase
{
    use RefreshDatabase;

    protected $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('owner');
    }

    public function test_pet_creation_creates_audit_log()
    {
        $this->actingAs($this->owner);

        $pet = Pet::factory()->create([
            'user_id' => $this->owner->id,
            'name' => 'Test Pet',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Pet::class,
            'auditable_id' => $pet->id,
            'event' => 'created',
            'user_id' => $this->owner->id,
        ]);
    }

    public function test_pet_update_creates_audit_log()
    {
        $this->actingAs($this->owner);

        $pet = Pet::factory()->create([
            'user_id' => $this->owner->id,
            'name' => 'Original Name',
        ]);

        // Clear the creation audit log
        AuditLog::query()->delete();

        $pet->update(['name' => 'Updated Name']);

        $auditLog = AuditLog::where('event', 'updated')->first();
        
        $this->assertNotNull($auditLog);
        $this->assertEquals(Pet::class, $auditLog->auditable_type);
        $this->assertEquals($pet->id, $auditLog->auditable_id);
        $this->assertEquals('updated', $auditLog->event);
        $this->assertArrayHasKey('name', $auditLog->new_values);
        $this->assertEquals('Updated Name', $auditLog->new_values['name']);
    }

    public function test_pet_deletion_creates_audit_log()
    {
        $this->actingAs($this->owner);

        $pet = Pet::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        $petId = $pet->id;

        // Clear the creation audit log
        AuditLog::query()->delete();

        $pet->delete();

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Pet::class,
            'auditable_id' => $petId,
            'event' => 'deleted',
            'user_id' => $this->owner->id,
        ]);
    }
}
