<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Real exchange rates (ECB reference rates published by Frankfurter).
 * Returns null on any failure: the UI shows an error state, never invented numbers.
 */
class CurrencyService
{
    public const CURRENCIES = [
        'USD' => 'US Dollar', 'EUR' => 'Euro', 'GBP' => 'British Pound', 'JPY' => 'Japanese Yen', 'CNY' => 'Chinese Yuan',
        'SGD' => 'Singapore Dollar', 'MYR' => 'Malaysian Ringgit', 'IDR' => 'Indonesian Rupiah', 'AUD' => 'Australian Dollar',
    ];

    /** @return array{base: string, date: string, rates: array<string, float>, fetched_at: string}|null */
    public function rates(string $base): ?array
    {
        if (! array_key_exists($base, self::CURRENCIES)) {
            return null;
        }
        $cfg = config('services.currency');

        return Cache::remember("fx:{$base}", now()->addMinutes(30), function () use ($cfg, $base) {
            try {
                $response = Http::baseUrl($cfg['base_url'])->timeout($cfg['timeout'])->acceptJson()
                    ->get('/latest', ['base' => $base, 'symbols' => implode(',', array_diff(array_keys(self::CURRENCIES), [$base]))]);

                if ($response->failed()) {
                    Log::warning('Currency API failed', ['status' => $response->status()]);

                    return null;
                }
                $rates = $response->json('rates');
                if (! is_array($rates) || $rates === []) {
                    return null;
                }

                return [
                    'base' => $base,
                    'date' => (string) $response->json('date'),
                    'rates' => array_map('floatval', $rates),
                    'fetched_at' => now()->toIso8601String(),
                ];
            } catch (Throwable $e) {
                Log::warning('Currency API error: '.$e->getMessage());

                return null;
            }
        });
    }

    /** @return array{rate: float, result: float, date: string, fetched_at: string}|null */
    public function convert(string $from, string $to, float $amount): ?array
    {
        if (! array_key_exists($from, self::CURRENCIES) || ! array_key_exists($to, self::CURRENCIES)) {
            return null;
        }
        if ($from === $to) {
            return ['rate' => 1.0, 'result' => $amount, 'date' => now()->toDateString(), 'fetched_at' => now()->toIso8601String()];
        }
        $data = $this->rates($from);
        if (! $data || ! isset($data['rates'][$to])) {
            return null;
        }

        return [
            'rate' => $data['rates'][$to],
            'result' => $amount * $data['rates'][$to],
            'date' => $data['date'],
            'fetched_at' => $data['fetched_at'],
        ];
    }
}
