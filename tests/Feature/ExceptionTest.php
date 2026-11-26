<?php

namespace Tests\Feature;

use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExceptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_reports_and_resolves_exception(): void
    {
        $s = Shipment::factory()->create();
        Sanctum::actingAs(User::factory()->operator()->create());

        $id = $this->postJson("/api/shipments/{$s->id}/exceptions", ['type' => 'CUSTOMS_HOLD', 'severity' => 'HIGH', 'description' => 'Docs missing'])
            ->assertCreated()->assertJsonPath('data.status', 'OPEN')->json('data.id');

        $this->patchJson("/api/exceptions/{$id}", ['status' => 'INVESTIGATING'])->assertOk();
        $this->patchJson("/api/exceptions/{$id}", ['status' => 'RESOLVED', 'resolution_notes' => 'Cleared'])->assertOk()->assertJsonPath('data.status', 'RESOLVED');

        $this->assertDatabaseHas('shipment_exceptions', ['id' => $id, 'status' => 'RESOLVED']);
        $this->assertNotNull(\App\Models\ShipmentException::find($id)->resolved_at);
    }

    public function test_resolved_exception_cannot_be_reopened(): void
    {
        $s = Shipment::factory()->create();
        Sanctum::actingAs(User::factory()->operator()->create());
        $id = $this->postJson("/api/shipments/{$s->id}/exceptions", ['type' => 'DAMAGED', 'severity' => 'LOW'])->json('data.id');
        $this->patchJson("/api/exceptions/{$id}", ['status' => 'RESOLVED'])->assertOk();

        $this->patchJson("/api/exceptions/{$id}", ['status' => 'OPEN'])->assertStatus(422);
    }

    public function test_invalid_type_is_rejected_and_customer_is_forbidden(): void
    {
        $s = Shipment::factory()->create();
        Sanctum::actingAs(User::factory()->operator()->create());
        $this->postJson("/api/shipments/{$s->id}/exceptions", ['type' => 'ALIENS', 'severity' => 'LOW'])->assertStatus(422);

        Sanctum::actingAs(User::factory()->create());
        $this->postJson("/api/shipments/{$s->id}/exceptions", ['type' => 'DAMAGED', 'severity' => 'LOW'])->assertForbidden();
    }

    public function test_exception_event_appears_in_timeline_as_exception(): void
    {
        $s = Shipment::factory()->create();
        Sanctum::actingAs(User::factory()->admin()->create());
        $this->postJson("/api/shipments/{$s->id}/exceptions", ['type' => 'WEATHER_DELAY', 'severity' => 'MEDIUM'])->assertCreated();

        $this->assertDatabaseHas('tracking_events', ['shipment_id' => $s->id, 'event_type' => 'EXCEPTION', 'is_exception' => true]);
    }
}
