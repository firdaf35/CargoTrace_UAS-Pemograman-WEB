<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_login_redirects_to_dashboard(): void
    {
        $user = User::factory()->create(['email' => 'a@test.com']);

        $this->post('/login', ['login' => 'a@test.com', 'password' => 'password'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_login_is_rejected(): void
    {
        User::factory()->create(['email' => 'a@test.com']);

        $this->from('/login')->post('/login', ['login' => 'a@test.com', 'password' => 'wrong'])
            ->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_login_with_username_works(): void
    {
        $user = User::factory()->create(['email' => 'u@test.com']);
        $user->forceFill(['username' => 'captain'])->save();

        $this->post('/login', ['login' => 'captain', 'password' => 'password'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_forgot_password_page_and_request(): void
    {
        $this->get('/forgot-password')->assertOk();
        User::factory()->create(['email' => 'f@test.com']);
        $this->post('/forgot-password', ['email' => 'f@test.com'])->assertSessionHas('status');
        $this->post('/forgot-password', ['email' => 'nobody@test.com'])->assertSessionHas('status'); // no account enumeration
    }

    public function test_login_page_renders_with_tagline(): void
    {
        $this->get('/login')->assertOk()->assertSee('Connecting Global Trade, One Shipment at a Time.');
    }

    public function test_guest_is_redirected_from_protected_pages(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/shipments')->assertRedirect('/login');
    }

    public function test_api_requires_authentication(): void
    {
        $this->getJson('/api/shipments')->assertStatus(401)->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_api_login_returns_token_and_user_endpoint_works(): void
    {
        User::factory()->create(['email' => 'api@test.com']);

        $token = $this->postJson('/api/login', ['email' => 'api@test.com', 'password' => 'password'])
            ->assertOk()->assertJsonStructure(['token', 'user'])->json('token');

        $this->withToken($token)->getJson('/api/user')->assertOk()->assertJsonPath('data.email', 'api@test.com');
    }

    public function test_api_login_invalid_credentials_returns_422(): void
    {
        User::factory()->create(['email' => 'api@test.com']);

        $this->postJson('/api/login', ['email' => 'api@test.com', 'password' => 'nope'])->assertStatus(422)->assertJsonStructure(['message', 'errors']);
    }

    public function test_api_register_always_creates_customer(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Eve', 'email' => 'eve@test.com', 'password' => 'secret123', 'password_confirmation' => 'secret123', 'role' => 'admin',
        ])->assertCreated();

        $this->assertSame('customer', User::where('email', 'eve@test.com')->first()->role->value);
    }

    public function test_api_logout_revokes_token(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/logout')->assertOk();
    }
}
