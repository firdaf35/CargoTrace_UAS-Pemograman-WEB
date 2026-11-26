<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Current weather at a coordinate via Open-Meteo. Informational only:
 * weather is NOT used as evidence/prediction for shipment delay.
 */
class WeatherService
{
    public const CODES = [
        0 => 'Clear sky', 1 => 'Mainly clear', 2 => 'Partly cloudy', 3 => 'Overcast',
        45 => 'Fog', 48 => 'Rime fog', 51 => 'Light drizzle', 53 => 'Drizzle', 55 => 'Dense drizzle',
        61 => 'Light rain', 63 => 'Rain', 65 => 'Heavy rain', 71 => 'Light snow', 73 => 'Snow', 75 => 'Heavy snow',
        80 => 'Rain showers', 81 => 'Rain showers', 82 => 'Violent rain showers', 95 => 'Thunderstorm', 96 => 'Thunderstorm with hail', 99 => 'Thunderstorm with hail',
    ];

    /** @return array{temperature: float, wind_speed: float, condition: string, time: string}|null */
    public function current(float $lat, float $lng): ?array
    {
        $cfg = config('services.weather');

        return Cache::remember(sprintf('weather:%.2f,%.2f', $lat, $lng), now()->addMinutes(30), function () use ($cfg, $lat, $lng) {
            try {
                $response = Http::baseUrl($cfg['base_url'])
                    ->timeout($cfg['timeout'])
                    ->acceptJson()
                    ->when($cfg['api_key'], fn ($r) => $r->withQueryParameters(['apikey' => $cfg['api_key']]))
                    ->get('/forecast', [
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'current' => 'temperature_2m,wind_speed_10m,weather_code',
                        'timezone' => 'auto',
                    ]);

                if ($response->failed()) {
                    Log::warning('Weather API failed', ['status' => $response->status()]);

                    return null;
                }

                $c = $response->json('current');
                if (! is_array($c) || ! isset($c['temperature_2m'])) {
                    return null;
                }

                return [
                    'temperature' => (float) $c['temperature_2m'],
                    'wind_speed' => (float) ($c['wind_speed_10m'] ?? 0),
                    'condition' => self::CODES[$c['weather_code'] ?? -1] ?? 'Unknown',
                    'time' => (string) ($c['time'] ?? ''),
                ];
            } catch (Throwable $e) {
                Log::warning('Weather API error: '.$e->getMessage());

                return null;
            }
        });
    }
}
