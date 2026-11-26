<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShipmentTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $override = []): array
    {
        return array_merge([
            'customer_id' => User::factory()->create()->id,
            'carrier_id' => Carrier::factory()->create()->id,
            'origin_id' => Location::factory()->create()->id,
            'destination_id' => Location::factory()->create()->id,
            'cargo_type' => 'Electronics',
            'weight_kg' => 1200.5,
            'volume_cbm' => 12,
            'expected_departure' => now()->addDay()->toDateTimeString(),
            'expected_arrival' => now()->addDays(20)->toDateTimeString(),
        ], $override);
    }

    public function test_operator_can_create_shipment_with_generated_number_and_created_status(): void
    {
        Sanctum::actingAs(User::factory()->operator()->create());

        $res = $this->postJson('/api/shipments', $this->payload())->assertCreated();

        $res->assertJsonPath('data.status', 'CREATED');
        $this->assertMatchesRegularExpression('/^SHP-\d{4}-\d{6}$/', $res->json('data.shipment_number'));
        $this->assertDatabaseCount('tracking_events', 1); // initial CREATED event
        $this->assertDatabaseHas('audit_logs', ['action' => 'shipment.created']);
    }

    public function test_validation_errors_return_422(): void
    {
        Sanctum::actingAs(User::factory()->operator()->create());

        $this->postJson('/api/shipments', [])->assertStatus(422)->assertJsonValidationErrors(['customer_id', 'carrier_id', 'cargo_type', 'weight_kg']);
        $this->postJson('/api/shipments', $this->payload(['expected_arrival' => now()->subDay()->toDateTimeString()]))
            ->assertStatus(422)->assertJsonValidationErrors('expected_arrival');
    }

    public function test_status_cannot_be_set_through_mass_assignment(): void
    {
        Sanctum::actingAs(User::factory()->operator()->create());

        $res = $this->postJson('/api/shipments', $this->payload(['status' => 'DELIVERED', 'is_delayed' => true]))->assertCreated();

        $res->assertJsonPath('data.status', 'CREATED');
        $res->assertJsonPath('data.delay.is_delayed', false);
    }

    public function test_customer_cannot_create_shipments(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/shipments', $this->payload())->assertForbidden();
    }

    public function test_customer_only_sees_own_shipments(): void
    {
        $me = User::factory()->create();
        Shipment::factory()->count(2)->create(['customer_id' => $me->id]);
        $other = Shipment::factory()->create();
        Sanctum::actingAs($me);

        $this->getJson('/api/shipments')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/shipments/'.$other->id)->assertForbidden();
    }

    public function test_customer_cannot_view_other_customer_shipment_in_web(): void
    {
        $other = Shipment::factory()->create();

        $this->actingAs(User::factory()->create())->get('/shipments/'.$other->id)->assertForbidden();
    }

    public function test_operator_updates_shipment_but_cannot_delete(): void
    {
        $shipment = Shipment::factory()->create();
        Sanctum::actingAs(User::factory()->operator()->create());

        $this->patchJson('/api/shipments/'.$shipment->id, ['cargo_type' => 'Machinery'])->assertOk()->assertJsonPath('data.cargo_type', 'Machinery');
        $this->deleteJson('/api/shipments/'.$shipment->id)->assertForbidden();
    }

    public function test_admin_can_delete_shipment_soft(): void
    {
        $shipment = Shipment::factory()->create();
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->deleteJson('/api/shipments/'.$shipment->id)->assertOk();
        $this->assertSoftDeleted('shipments', ['id' => $shipment->id]);
        $this->getJson('/api/shipments/'.$shipment->id)->assertNotFound();
    }

    public function test_filter_and_pagination(): void
    {
        Shipment::factory()->count(12)->create();
        Shipment::factory()->create(['is_delayed' => true]);
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson('/api/shipments?per_page=5')->assertOk()->assertJsonCount(5, 'data')->assertJsonPath('meta.total', 13);
        $this->getJson('/api/shipments?delayed=1')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_only_admin_can_create_carriers_via_api(): void
    {
        Sanctum::actingAs(User::factory()->operator()->create());
        $this->postJson('/api/carriers', ['name' => 'X Lines', 'code' => 'XLN'])->assertForbidden();

        Sanctum::actingAs(User::factory()->admin()->create());
        $this->postJson('/api/carriers', ['name' => 'X Lines', 'code' => 'XLN'])->assertCreated();
    }
}
