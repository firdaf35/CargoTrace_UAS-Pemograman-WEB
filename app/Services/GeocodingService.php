<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Geocoding via OpenStreetMap Nominatim (Laravel HTTP Client).
 * Handles timeout, HTTP errors and empty results by returning null (never throws).
 */
class GeocodingService
{
    /** @return array{lat: float, lng: float, display_name: string, country_code: ?string}|null */
    public function geocode(string $query): ?array
    {
        $query = trim($query);
        if ($query === '') {
            return null;
        }

        $cfg = config('services.geocoding');

        return Cache::remember('geocode:'.md5(mb_strtolower($query)), now()->addDay(), function () use ($cfg, $query) {
            try {
                $response = Http::baseUrl($cfg['base_url'])
                    ->withHeaders(['User-Agent' => $cfg['user_agent']])
                    ->timeout($cfg['timeout'])
                    ->acceptJson()
                    ->get('/search', ['q' => $query, 'format' => 'jsonv2', 'limit' => 1, 'addressdetails' => 1]);

                if ($response->failed()) {
                    Log::warning('Geocoding failed', ['status' => $response->status()]);

                    return null;
                }

                $first = $response->json(0);
                if (! is_array($first) || ! isset($first['lat'], $first['lon'])) {
                    return null; // empty result
                }

                return ['lat' => (float) $first['lat'], 'lng' => (float) $first['lon'], 'display_name' => $first['display_name'] ?? $query, 'country_code' => isset($first['address']['country_code']) ? strtoupper($first['address']['country_code']) : null];
            } catch (Throwable $e) {
                Log::warning('Geocoding error: '.$e->getMessage());

                return null; // timeout / connection error
            }
        });
    }
}
