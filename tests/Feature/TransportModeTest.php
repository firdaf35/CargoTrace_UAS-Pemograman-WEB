<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use App\Services\JourneyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TransportModeTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $o = []): array
    {
        return array_merge([
            'customer_id' => User::factory()->create()->id,
            'carrier_id' => Carrier::factory()->create()->id,
            'origin_id' => Location::factory()->create()->id,
            'destination_id' => Location::factory()->create()->id,
            'cargo_type' => 'Electronics',
            'weight_kg' => 500,
            'expected_departure' => now()->addDay()->toDateTimeString(),
            'expected_arrival' => now()->addDays(3)->toDateTimeString(),
        ], $o);
    }

    public function test_default_mode_is_sea(): void
    {
        Sanctum::actingAs(User::factory()->operator()->create());

        $this->postJson('/api/shipments', $this->payload(['vessel_name' => 'MV Test', 'container_number' => 'MSKU1234567']))
            ->assertCreated()->assertJsonPath('data.transport_mode', 'sea')->assertJsonPath('data.asset.Vessel', 'MV Test');
    }

    public function test_air_shipment_keeps_only_air_fields(): void
    {
        Sanctum::actingAs(User::factory()->operator()->create());

        $res = $this->postJson('/api/shipments', $this->payload([
            'transport_mode' => 'air', 'flight_number' => 'GA-123', 'airline' => 'Garuda Cargo', 'vessel_name' => 'ignored',
        ]))->assertCreated();

        $res->assertJsonPath('data.transport_mode', 'air')->assertJsonPath('data.asset.Flight No.', 'GA-123');
        $this->assertNull(Shipment::first()->vessel_name); // fields of other modes are dropped
    }

    public function test_invalid_mode_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->operator()->create());

        $this->postJson('/api/shipments', $this->payload(['transport_mode' => 'rocket']))->assertStatus(422)->assertJsonValidationErrors('transport_mode');
    }

    public function test_filter_by_transport_mode(): void
    {
        Shipment::factory()->count(2)->create(['transport_mode' => 'sea']);
        Shipment::factory()->count(3)->create(['transport_mode' => 'air']);
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson('/api/shipments?mode=air')->assertOk()->assertJsonCount(3, 'data');
        $this->getJson('/api/shipments?mode=land')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/shipments?mode=boat')->assertStatus(422);
    }

    public function test_journey_is_not_complete_until_delivered(): void
    {
        $s = Shipment::factory()->create();
        Sanctum::actingAs(User::factory()->operator()->create());
        $this->postJson("/api/shipments/{$s->id}/tracking", ['event_type' => 'BOOKED'])->assertCreated();
        $this->postJson("/api/shipments/{$s->id}/tracking", ['event_type' => 'PICKED_UP'])->assertCreated();
        $this->postJson("/api/shipments/{$s->id}/tracking", ['event_type' => 'IN_TRANSIT'])->assertCreated();
        $this->postJson("/api/shipments/{$s->id}/tracking", ['event_type' => 'ARRIVED_AT_HUB'])->assertCreated();
        $this->postJson("/api/shipments/{$s->id}/tracking", ['event_type' => 'OUT_FOR_DELIVERY'])->assertCreated();

        $stages = collect((new JourneyService)->build($s->fresh()->load('trackingEvents')))->keyBy('key');

        $this->assertTrue($stages['picked_up']['done']);
        $this->assertTrue($stages['hub']['done']);
        $this->assertTrue($stages['out']['done']);
        $this->assertFalse($stages['customs']['done']);
        $this->assertFalse($stages['delivered']['done']); // never complete before status DELIVERED

        $this->postJson("/api/shipments/{$s->id}/tracking", ['event_type' => 'DELIVERED'])->assertCreated();
        $stages = collect((new JourneyService)->build($s->fresh()->load('trackingEvents')))->keyBy('key');
        $this->assertTrue($stages['delivered']['done']);
    }

    public function test_tracking_map_data_is_scoped_and_filtered(): void
    {
        $me = User::factory()->create();
        Shipment::factory()->create(['customer_id' => $me->id, 'transport_mode' => 'air']);
        Shipment::factory()->create(); // someone else's
        $this->actingAs($me);

        $this->getJson('/tracking/data')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.mode', 'air');
        $this->getJson('/tracking/data?mode=sea')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/tracking/data?mode=zzz')->assertStatus(422);
    }

    public function test_tracking_map_marks_position_as_last_reported(): void
    {
        $s = Shipment::factory()->create();
        \App\Models\TrackingEvent::create([
            'shipment_id' => $s->id, 'event_type' => 'NOTE', 'status_after' => 'CREATED',
            'latitude' => 1.5, 'longitude' => 103.7, 'location_name' => 'Johor', 'occurred_at' => now(),
        ]);

        $this->actingAs(User::factory()->admin()->create())->getJson('/tracking/data')
            ->assertOk()->assertJsonPath('data.0.current.kind', 'last_reported')->assertJsonPath('data.0.current.name', 'Johor');
    }

    public function test_customer_can_open_weather_currency_settings_but_not_management_pages(): void
    {
        \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::response([], 500)]);
        $c = User::factory()->create();

        $this->actingAs($c)->get('/weather')->assertOk();
        $this->actingAs($c)->get('/currency')->assertOk()->assertSee('currently unavailable');
        $this->actingAs($c)->get('/settings')->assertOk();
        $this->actingAs($c)->get('/transport')->assertForbidden();
        $this->actingAs($c)->get('/customers')->assertForbidden();
    }

    public function test_profile_update_requires_current_password_for_new_password(): void
    {
        $u = User::factory()->create();

        $this->actingAs($u)->put('/settings/profile', ['name' => 'New Name', 'password' => 'newsecret123', 'password_confirmation' => 'newsecret123'])
            ->assertSessionHasErrors('current_password');
        $this->actingAs($u)->put('/settings/profile', ['name' => 'New Name', 'current_password' => 'password', 'password' => 'newsecret123', 'password_confirmation' => 'newsecret123'])
            ->assertSessionHasNoErrors();
        $this->assertSame('New Name', $u->fresh()->name);
    }
}
