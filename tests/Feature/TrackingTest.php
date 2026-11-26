<?php

namespace Tests\Feature;

use App\Enums\ShipmentStatus;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_transition_appends_event_and_updates_status(): void
    {
        $s = Shipment::factory()->create();
        Sanctum::actingAs(User::factory()->operator()->create());

        $this->postJson("/api/shipments/{$s->id}/tracking", ['event_type' => 'BOOKED'])->assertCreated()->assertJsonPath('shipment_status', 'BOOKED');
        $this->postJson("/api/shipments/{$s->id}/tracking", ['event_type' => 'PICKED_UP', 'location_id' => Location::factory()->create()->id])->assertCreated();

        $this->assertSame(ShipmentStatus::PICKED_UP, $s->refresh()->status);
        $this->assertDatabaseCount('tracking_events', 2); // history kept, nothing overwritten
    }

    public function test_invalid_status_jump_is_rejected(): void
    {
        $s = Shipment::factory()->create(); // CREATED
        Sanctum::actingAs(User::factory()->operator()->create());

        $this->postJson("/api/shipments/{$s->id}/tracking", ['event_type' => 'DELIVERED'])
            ->assertStatus(422)->assertJsonValidationErrors('event_type');
        $this->assertSame(ShipmentStatus::CREATED, $s->refresh()->status);
    }

    public function test_terminal_status_cannot_receive_more_events(): void
    {
        $s = Shipment::factory()->status(ShipmentStatus::DELIVERED)->create();
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->postJson("/api/shipments/{$s->id}/tracking", ['event_type' => 'IN_TRANSIT'])->assertStatus(422);
        $this->postJson("/api/shipments/{$s->id}/tracking", ['event_type' => 'NOTE'])->assertStatus(422);
    }

    public function test_event_time_must_not_go_backwards_or_into_the_future(): void
    {
        $s = Shipment::factory()->create();
        Sanctum::actingAs(User::factory()->operator()->create());

        $this->postJson("/api/shipments/{$s->id}/tracking", ['event_type' => 'BOOKED'])->assertCreated();
        $this->postJson("/api/shipments/{$s->id}/tracking", ['event_type' => 'NOTE', 'occurred_at' => now()->subDays(2)->toDateTimeString()])
            ->assertStatus(422)->assertJsonValidationErrors('occurred_at');
        $this->postJson("/api/shipments/{$s->id}/tracking", ['event_type' => 'NOTE', 'occurred_at' => now()->addDays(2)->toDateTimeString()])
            ->assertStatus(422)->assertJsonValidationErrors('occurred_at');
    }

    public function test_latitude_requires_longitude(): void
    {
        $s = Shipment::factory()->create();
        Sanctum::actingAs(User::factory()->operator()->create());

        $this->postJson("/api/shipments/{$s->id}/tracking", ['event_type' => 'NOTE', 'latitude' => 10])->assertStatus(422)->assertJsonValidationErrors('longitude');
    }

    public function test_customer_cannot_add_events_but_can_read_timeline_of_own_shipment(): void
    {
        $me = User::factory()->create();
        $s = Shipment::factory()->create(['customer_id' => $me->id]);
        Sanctum::actingAs($me);

        $this->postJson("/api/shipments/{$s->id}/tracking", ['event_type' => 'BOOKED'])->assertForbidden();
        $this->getJson("/api/shipments/{$s->id}/tracking")->assertOk()->assertJsonStructure(['data', 'current_location']);
    }

    public function test_customer_cannot_read_timeline_of_someone_elses_shipment(): void
    {
        $s = Shipment::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/shipments/{$s->id}/tracking")->assertForbidden();
    }

    public function test_delivery_sets_actual_arrival_and_delay(): void
    {
        $s = Shipment::factory()->status(ShipmentStatus::IN_TRANSIT)->create([
            'expected_arrival' => now()->subDays(2),
            'estimated_arrival' => now()->subDays(2),
        ]);
        Sanctum::actingAs(User::factory()->operator()->create());

        $this->postJson("/api/shipments/{$s->id}/tracking", ['event_type' => 'DELIVERED'])->assertCreated();

        $s->refresh();
        $this->assertNotNull($s->actual_arrival);
        $this->assertTrue($s->is_delayed); // ~48h late >= 12h threshold
        $this->assertSame('HIGH', $s->delay_severity->value);
    }
}
