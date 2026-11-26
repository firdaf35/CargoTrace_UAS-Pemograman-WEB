<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Global Weather page data. Real APIs only:
 *  - OpenWeatherMap (when OPENWEATHER_API_KEY is configured), otherwise
 *  - Open-Meteo (keyless).
 * Every method returns null on failure so controllers can render an error state.
 */
class WeatherForecastService
{
    /** @return array<int, array<string, mixed>>|null */
    public function search(string $query): ?array
    {
        $query = trim($query);
        $cfg = config('services.geocoding_search');

        return Cache::remember('citysearch:'.md5(mb_strtolower($query)), now()->addDay(), function () use ($cfg, $query) {
            try {
                $r = Http::baseUrl($cfg['base_url'])->timeout($cfg['timeout'])->acceptJson()
                    ->get('/search', ['name' => $query, 'count' => 6, 'language' => 'en', 'format' => 'json']);
                if ($r->failed()) {
                    Log::warning('City search failed', ['status' => $r->status()]);

                    return null;
                }

                return collect($r->json('results') ?? [])->map(fn ($x) => [
                    'name' => $x['name'] ?? '',
                    'region' => $x['admin1'] ?? null,
                    'country' => $x['country'] ?? '',
                    'country_code' => isset($x['country_code']) ? strtoupper($x['country_code']) : null,
                    'lat' => (float) $x['latitude'],
                    'lng' => (float) $x['longitude'],
                ])->values()->all();
            } catch (Throwable $e) {
                Log::warning('City search error: '.$e->getMessage());

                return null;
            }
        });
    }

    /** @return array<string, mixed>|null */
    public function forecast(float $lat, float $lng): ?array
    {
        $key = config('services.openweather.api_key');
        $provider = $key ? 'owm' : 'meteo';

        return Cache::remember(sprintf('wxpage:%s:%.2f,%.2f', $provider, $lat, $lng), now()->addMinutes(30), function () use ($lat, $lng, $key) {
            if ($key) {
                $data = $this->viaOpenWeather($lat, $lng, $key);
                if ($data) {
                    return $data;
                }
                Log::warning('OpenWeatherMap unavailable, falling back to Open-Meteo.');
            }

            return $this->viaOpenMeteo($lat, $lng);
        });
    }

    private function viaOpenWeather(float $lat, float $lng, string $key): ?array
    {
        $cfg = config('services.openweather');
        try {
            $http = Http::baseUrl($cfg['base_url'])->timeout($cfg['timeout'])->acceptJson();
            $params = ['lat' => $lat, 'lon' => $lng, 'units' => 'metric', 'appid' => $key];
            $now = $http->get('/data/2.5/weather', $params);
            $fc = $http->get('/data/2.5/forecast', $params);

            if ($now->failed() || $fc->failed() || ! $now->json('main')) {
                return null;
            }

            $days = collect($fc->json('list') ?? [])->groupBy(fn ($e) => substr($e['dt_txt'], 0, 10))->map(function ($rows, $date) {
                $mid = $rows->sortBy(fn ($e) => abs((int) substr($e['dt_txt'], 11, 2) - 12))->first();

                return [
                    'date' => $date,
                    'min' => round($rows->min('main.temp_min')),
                    'max' => round($rows->max('main.temp_max')),
                    'condition' => $mid['weather'][0]['description'] ?? '',
                    'icon_url' => isset($mid['weather'][0]['icon']) ? "https://openweathermap.org/img/wn/{$mid['weather'][0]['icon']}@2x.png" : null,
                    'icon_emoji' => null,
                    'precip' => (int) round($rows->max('pop') * 100),
                ];
            })->values()->filter(fn ($d) => $d['date'] !== now()->toDateString())->take(5)->values()->all();

            $w = $now->json('weather.0');

            return [
                'provider' => 'OpenWeatherMap',
                'temperature' => round($now->json('main.temp'), 1),
                'feels_like' => round($now->json('main.feels_like'), 1),
                'humidity' => (int) $now->json('main.humidity'),
                'wind_kmh' => round(($now->json('wind.speed') ?? 0) * 3.6, 1),
                'condition' => ucfirst($w['description'] ?? ''),
                'icon_url' => isset($w['icon']) ? "https://openweathermap.org/img/wn/{$w['icon']}@2x.png" : null,
                'icon_emoji' => null,
                'daily' => $days,
                'fetched_at' => now()->toIso8601String(),
            ];
        } catch (Throwable $e) {
            Log::warning('OpenWeatherMap error: '.$e->getMessage());

            return null;
        }
    }

    private function viaOpenMeteo(float $lat, float $lng): ?array
    {
        $cfg = config('services.weather');
        try {
            $r = Http::baseUrl($cfg['base_url'])->timeout($cfg['timeout'])->acceptJson()
                ->get('/forecast', [
                    'latitude' => $lat, 'longitude' => $lng, 'timezone' => 'auto', 'forecast_days' => 6,
                    'current' => 'temperature_2m,apparent_temperature,relative_humidity_2m,wind_speed_10m,weather_code',
                    'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max',
                ]);
            if ($r->failed() || ! is_array($r->json('current')) || ! isset($r->json('current')['temperature_2m'])) {
                return null;
            }
            $c = $r->json('current');
            $d = $r->json('daily') ?? [];
            $days = [];
            foreach (array_slice($d['time'] ?? [], 1, 5, true) as $i => $date) {
                $code = $d['weather_code'][$i] ?? null;
                $days[] = [
                    'date' => $date,
                    'min' => round($d['temperature_2m_min'][$i] ?? 0),
                    'max' => round($d['temperature_2m_max'][$i] ?? 0),
                    'condition' => WeatherService::CODES[$code] ?? 'Unknown',
                    'icon_url' => null,
                    'icon_emoji' => $this->emoji($code),
                    'precip' => (int) ($d['precipitation_probability_max'][$i] ?? 0),
                ];
            }

            return [
                'provider' => 'Open-Meteo',
                'temperature' => round((float) $c['temperature_2m'], 1),
                'feels_like' => round((float) ($c['apparent_temperature'] ?? $c['temperature_2m']), 1),
                'humidity' => (int) ($c['relative_humidity_2m'] ?? 0),
                'wind_kmh' => round((float) ($c['wind_speed_10m'] ?? 0), 1),
                'condition' => WeatherService::CODES[$c['weather_code'] ?? -1] ?? 'Unknown',
                'icon_url' => null,
                'icon_emoji' => $this->emoji($c['weather_code'] ?? null),
                'daily' => $days,
                'fetched_at' => now()->toIso8601String(),
            ];
        } catch (Throwable $e) {
            Log::warning('Open-Meteo forecast error: '.$e->getMessage());

            return null;
        }
    }

    private function emoji(?int $code): string
    {
        return match (true) {
            $code === 0 => '☀️',
            $code === 1 || $code === 2 => '🌤️',
            $code === 3 => '☁️',
            $code === 45 || $code === 48 => '🌫️',
            $code !== null && $code >= 51 && $code <= 57 => '🌦️',
            $code !== null && $code >= 61 && $code <= 67 => '🌧️',
            $code !== null && $code >= 71 && $code <= 77 => '❄️',
            $code !== null && $code >= 80 && $code <= 82 => '🌧️',
            $code !== null && $code >= 95 => '⛈️',
            default => '🌡️',
        };
    }
}
