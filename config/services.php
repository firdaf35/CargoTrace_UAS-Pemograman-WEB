<?php

return [
    // OpenStreetMap Nominatim (no API key; a descriptive User-Agent is required by its usage policy).
    'geocoding' => [
        'base_url' => env('GEOCODING_BASE_URL', 'https://nominatim.openstreetmap.org'),
        'user_agent' => env('GEOCODING_USER_AGENT', 'CargoTrace-UAS/1.0'),
        'timeout' => (int) env('EXTERNAL_API_TIMEOUT', 5),
    ],
    // Global Weather page: OpenWeatherMap when OPENWEATHER_API_KEY is set, otherwise Open-Meteo (keyless, real data).
    'openweather' => [
        'base_url' => env('OPENWEATHER_BASE_URL', 'https://api.openweathermap.org'),
        'api_key' => env('OPENWEATHER_API_KEY'),
        'timeout' => (int) env('EXTERNAL_API_TIMEOUT', 5),
    ],
    'geocoding_search' => [
        'base_url' => env('CITY_SEARCH_BASE_URL', 'https://geocoding-api.open-meteo.com/v1'),
        'timeout' => (int) env('EXTERNAL_API_TIMEOUT', 5),
    ],
    // Currency Exchange page (ECB reference rates via Frankfurter, no key required).
    'currency' => [
        'base_url' => env('CURRENCY_BASE_URL', 'https://api.frankfurter.dev/v1'),
        'timeout' => (int) env('EXTERNAL_API_TIMEOUT', 5),
    ],
    // Open-Meteo (free, key optional for commercial plan). Informational only - NOT a delay predictor.
    'weather' => [
        'base_url' => env('WEATHER_BASE_URL', 'https://api.open-meteo.com/v1'),
        'api_key' => env('WEATHER_API_KEY'),
        'timeout' => (int) env('EXTERNAL_API_TIMEOUT', 5),
    ],
];
