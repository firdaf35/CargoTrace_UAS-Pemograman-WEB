<?php

namespace Tests\Unit;

use App\Services\GeocodingService;
use App\Services\WeatherService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExternalApiTest extends TestCase
{
    public function test_geocoding_success(): void
    {
        Http::fake(['*' => Http::response([['lat' => '51.95', 'lon' => '4.14', 'display_name' => 'Rotterdam']], 200)]);

        $r = (new GeocodingService)->geocode('Port of Rotterdam');

        $this->assertSame(51.95, $r['lat']);
        $this->assertSame(4.14, $r['lng']);
    }

    public function test_geocoding_handles_empty_result_error_and_timeout(): void
    {
        Http::fake(['*' => Http::response([], 200)]);
        $this->assertNull((new GeocodingService)->geocode('nowhere-empty'));

        Http::fake(['*' => Http::response('boom', 500)]);
        $this->assertNull((new GeocodingService)->geocode('nowhere-500'));

        Http::fake(fn () => throw new ConnectionException('timeout'));
        $this->assertNull((new GeocodingService)->geocode('nowhere-timeout'));
    }

    public function test_weather_success_and_failure(): void
    {
        Http::fake(['*' => Http::response(['current' => ['temperature_2m' => 28.4, 'wind_speed_10m' => 12.1, 'weather_code' => 63, 'time' => '2026-01-01T10:00']], 200)]);
        $w = (new WeatherService)->current(1.26, 103.84);
        $this->assertSame('Rain', $w['condition']);
        $this->assertSame(28.4, $w['temperature']);

        Http::fake(['*' => Http::response('down', 503)]);
        $this->assertNull((new WeatherService)->current(50.5, 5.5));

        Http::fake(['*' => Http::response(['current' => null], 200)]);
        $this->assertNull((new WeatherService)->current(60.1, 6.1));
    }
}
