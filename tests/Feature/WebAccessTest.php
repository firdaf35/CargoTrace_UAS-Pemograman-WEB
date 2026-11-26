<?php

namespace Tests\Feature;

use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Authorization is enforced on the server, not only by hiding buttons. */
class WebAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_is_blocked_from_staff_and_admin_pages(): void
    {
        $c = User::factory()->create();

        foreach (['/shipments/create', '/reports', '/carriers', '/transport', '/customers', '/locations', '/users', '/audit-logs'] as $url) {
            $this->actingAs($c)->get($url)->assertForbidden();
        }
    }

    public function test_operator_is_blocked_from_admin_pages(): void
    {
        $o = User::factory()->operator()->create();

        foreach (['/locations', '/users', '/audit-logs'] as $url) {
            $this->actingAs($o)->get($url)->assertForbidden();
        }
        $this->actingAs($o)->get('/shipments/create')->assertOk();
    }

    public function test_admin_pages_and_dashboard_render(): void
    {
        Http::fake(['*' => Http::response([], 500)]); // external APIs must fail gracefully
        Shipment::factory()->count(3)->create();
        $a = User::factory()->admin()->create();

        foreach (['/dashboard', '/shipments', '/shipments?mode=air', '/tracking', '/exceptions', '/carriers', '/transport', '/transport?mode=sea', '/customers', '/locations', '/users', '/audit-logs', '/reports', '/notifications', '/weather', '/currency', '/settings'] as $url) {
            $this->actingAs($a)->get($url)->assertOk();
        }
    }

    public function test_customer_cannot_post_tracking_or_delete_via_web(): void
    {
        $me = User::factory()->create();
        $s = Shipment::factory()->create(['customer_id' => $me->id]);

        $this->actingAs($me)->post("/shipments/{$s->id}/tracking", ['event_type' => 'BOOKED'])->assertForbidden();
        $this->actingAs($me)->delete("/shipments/{$s->id}")->assertForbidden();
    }

    public function test_csrf_protected_logout_works_for_authenticated_user(): void
    {
        $this->actingAs(User::factory()->create())->post('/logout')->assertRedirect('/login');
    }
}
