<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CurrencyWeatherTest extends TestCase
{
    use RefreshDatabase;

    private function ratesFake(): void
    {
        Http::fake(['*' => Http::response(['base' => 'USD', 'date' => '2026-11-25', 'rates' => ['EUR' => 0.9, 'GBP' => 0.8, 'JPY' => 150.0, 'CNY' => 7.2, 'SGD' => 1.3, 'MYR' => 4.5, 'IDR' => 16000.0, 'AUD' => 1.5]], 200)]);
    }

    public function test_conversion_uses_real_rate_from_api(): void
    {
        $this->ratesFake();

        $j = $this->actingAs(User::factory()->create())->getJson('/currency/convert?from=USD&to=IDR&amount=10')
            ->assertOk()->assertJsonPath('data.date', '2026-11-25')->json('data');

        $this->assertEquals(16000, $j['rate']);
        $this->assertEquals(160000, $j['result']);
    }

    public function test_same_currency_needs_no_api(): void
    {
        Http::fake(fn () => throw new ConnectionException('down'));

        $j = $this->actingAs(User::factory()->create())->getJson('/currency/convert?from=EUR&to=EUR&amount=5')->assertOk()->json('data');
        $this->assertEquals(5, $j['result']);
        $this->assertEquals(1, $j['rate']);
    }

    public function test_conversion_validates_input_and_handles_api_failure(): void
    {
        $this->actingAs($u = User::factory()->create());
        $this->getJson('/currency/convert?from=XXX&to=IDR&amount=1')->assertStatus(422);
        $this->getJson('/currency/convert?from=USD&to=IDR&amount=-1')->assertStatus(422);

        Http::fake(['*' => Http::response('down', 503)]);
        $this->getJson('/currency/convert?from=USD&to=IDR&amount=1')->assertStatus(503)->assertJsonStructure(['message']);
    }

    public function test_currency_page_lists_rates_or_error_state(): void
    {
        $this->ratesFake();
        $this->actingAs(User::factory()->create())->get('/currency')->assertOk()->assertSee('16,000')->assertSee('ECB');

        Http::fake(fn () => throw new ConnectionException('timeout'));
        \Illuminate\Support\Facades\Cache::flush();
        $this->get('/currency?base=EUR')->assertOk()->assertSee('currently unavailable');
    }

    public function test_city_search_and_forecast_via_open_meteo(): void
    {
        config(['services.openweather.api_key' => null]);
        Http::fake([
            '*geocoding*' => Http::response(['results' => [['name' => 'Rotterdam', 'country' => 'Netherlands', 'country_code' => 'NL', 'latitude' => 51.9, 'longitude' => 4.47]]], 200),
            '*' => Http::response([
                'current' => ['temperature_2m' => 9.5, 'apparent_temperature' => 7.0, 'relative_humidity_2m' => 80, 'wind_speed_10m' => 20.0, 'weather_code' => 3, 'time' => '2026-11-25T10:00'],
                'daily' => [
                    'time' => ['2026-11-25', '2026-11-26', '2026-11-27', '2026-11-28', '2026-11-29', '2026-11-30'],
                    'weather_code' => [3, 61, 0, 2, 45, 95], 'temperature_2m_max' => [10, 11, 12, 9, 8, 7], 'temperature_2m_min' => [5, 6, 7, 4, 3, 2],
                    'precipitation_probability_max' => [10, 60, 0, 5, 20, 80],
                ],
            ], 200),
        ]);
        $this->actingAs(User::factory()->create());

        $this->getJson('/weather/search?q=Rotterdam')->assertOk()->assertJsonPath('results.0.country_code', 'NL');
        $res = $this->getJson('/weather/data?lat=51.9&lng=4.47')->assertOk();
        $res->assertJsonPath('data.provider', 'Open-Meteo')->assertJsonPath('data.humidity', 80)->assertJsonCount(5, 'data.daily');
        $this->assertEquals(7, $res->json('data.feels_like'));
    }

    public function test_weather_endpoints_validate_and_handle_failure(): void
    {
        $this->actingAs(User::factory()->create());
        $this->getJson('/weather/search?q=a')->assertStatus(422);
        $this->getJson('/weather/data?lat=999&lng=0')->assertStatus(422);

        Http::fake(['*' => Http::response('down', 500)]);
        $this->getJson('/weather/data?lat=10&lng=10')->assertStatus(503)->assertJsonStructure(['message']);
        $this->getJson('/weather/search?q=Jakarta')->assertStatus(503);
    }

    public function test_openweathermap_is_used_when_key_is_configured(): void
    {
        config(['services.openweather.api_key' => 'test-key']);
        Http::fake([
            '*data/2.5/weather*' => Http::response(['main' => ['temp' => 30.2, 'feels_like' => 34.0, 'humidity' => 70], 'wind' => ['speed' => 5], 'weather' => [['description' => 'light rain', 'icon' => '10d']]], 200),
            '*data/2.5/forecast*' => Http::response(['list' => [['dt_txt' => now()->addDay()->format('Y-m-d').' 12:00:00', 'main' => ['temp_min' => 25, 'temp_max' => 31], 'weather' => [['description' => 'clear sky', 'icon' => '01d']], 'pop' => 0.2]]], 200),
        ]);

        $res = $this->actingAs(User::factory()->create())->getJson('/weather/data?lat=-6.2&lng=106.8')
            ->assertOk()->assertJsonPath('data.provider', 'OpenWeatherMap');
        $this->assertEquals(18, $res->json('data.wind_kmh'));
        $this->assertEquals(31, $res->json('data.daily.0.max'));
    }
}
