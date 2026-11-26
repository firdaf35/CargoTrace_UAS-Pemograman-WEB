<?php

namespace Database\Seeders;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use App\Services\ExceptionService;
use App\Services\ShipmentService;
use App\Services\TrackingService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Safe to run on an existing database (php artisan db:seed --class=ModeDemoSeeder):
 *  - fills usernames, country codes and sea-freight vessel/container data on EXISTING rows,
 *  - adds airports / logistics hubs,
 *  - adds air + land demo shipments only if none exist yet.
 * Nothing is deleted or overwritten.
 */
class ModeDemoSeeder extends Seeder
{
    private const COUNTRY_CODES = [
        'Indonesia' => 'ID', 'Malaysia' => 'MY', 'Thailand' => 'TH', 'Singapore' => 'SG', 'Sri Lanka' => 'LK',
        'United Arab Emirates' => 'AE', 'Egypt' => 'EG', 'Netherlands' => 'NL', 'Germany' => 'DE', 'Belgium' => 'BE',
        'United Kingdom' => 'GB', 'Hong Kong' => 'HK', 'France' => 'FR', 'China' => 'CN', 'Japan' => 'JP',
        'Australia' => 'AU', 'United States' => 'US',
    ];

    public function run(): void
    {
        mt_srand(77);

        // 1. usernames for existing users
        User::whereNull('username')->get()->each(function (User $u) {
            $base = Str::slug(strstr($u->email, '@', true) ?: $u->name, '_');
            $name = $base;
            $i = 1;
            while (User::where('username', $name)->exists()) {
                $name = $base.(++$i);
            }
            $u->forceFill(['username' => $name])->save();
        });

        // 2. new airports + hubs, then country codes (flags)
        $defs = [
            'cgk' => ['Soekarno-Hatta International Airport', 'Jakarta', 'Indonesia', 'AIRPORT', -6.1256, 106.6559],
            'sinA' => ['Singapore Changi Airport', 'Singapore', 'Singapore', 'AIRPORT', 1.3644, 103.9915],
            'kul' => ['Kuala Lumpur International Airport', 'Sepang', 'Malaysia', 'AIRPORT', 2.7456, 101.7072],
            'hkg' => ['Hong Kong International Airport', 'Hong Kong', 'Hong Kong', 'AIRPORT', 22.3080, 113.9185],
            'dxb' => ['Dubai International Airport', 'Dubai', 'United Arab Emirates', 'AIRPORT', 25.2532, 55.3657],
            'ams' => ['Amsterdam Airport Schiphol', 'Amsterdam', 'Netherlands', 'AIRPORT', 52.3105, 4.7683],
            'fra' => ['Frankfurt Airport', 'Frankfurt', 'Germany', 'AIRPORT', 50.0379, 8.5622],
            'lhr' => ['London Heathrow Airport', 'London', 'United Kingdom', 'AIRPORT', 51.4700, -0.4543],
            'jktW' => ['Jakarta Cargo Warehouse', 'Jakarta', 'Indonesia', 'WAREHOUSE', -6.2297, 106.9980],
            'bdg' => ['Bandung Logistics Hub', 'Bandung', 'Indonesia', 'HUB', -6.9175, 107.6191],
            'smg' => ['Semarang Distribution Center', 'Semarang', 'Indonesia', 'HUB', -6.9667, 110.4167],
            'subW' => ['Surabaya Cargo Warehouse', 'Surabaya', 'Indonesia', 'WAREHOUSE', -7.2575, 112.7521],
            'sinJ' => ['Jurong Logistics Hub', 'Singapore', 'Singapore', 'HUB', 1.3329, 103.7436],
            'kulH' => ['Shah Alam Logistics Hub', 'Shah Alam', 'Malaysia', 'HUB', 3.0738, 101.5183],
            'rtmH' => ['Rotterdam Distribution Park', 'Rotterdam', 'Netherlands', 'WAREHOUSE', 51.9244, 4.4777],
            'antH' => ['Antwerp Logistics Park', 'Antwerp', 'Belgium', 'HUB', 51.2194, 4.4025],
            'fraH' => ['Frankfurt Logistics Hub', 'Frankfurt', 'Germany', 'HUB', 50.1109, 8.6821],
            'hamH' => ['Hamburg Distribution Center', 'Hamburg', 'Germany', 'WAREHOUSE', 53.5511, 9.9937],
        ];
        $loc = [];
        foreach ($defs as $key => [$name, $city, $country, $type, $lat, $lng]) {
            $loc[$key] = Location::firstOrCreate(
                ['name' => $name, 'city' => $city, 'country' => $country],
                ['type' => $type, 'latitude' => $lat, 'longitude' => $lng]
            );
        }
        Location::whereNull('country_code')->get()->each(function (Location $l) {
            if (isset(self::COUNTRY_CODES[$l->country])) {
                $l->update(['country_code' => self::COUNTRY_CODES[$l->country]]);
            }
        });

        // 3. sea freight details on existing shipments
        $vessels = ['MV Nusantara Star', 'MV Pacific Voyager', 'MV Ocean Harmony', 'MV Selat Express', 'MV Aurora Trader', 'MV Straits Pioneer', 'MV Rotterdam Link'];
        Shipment::where('transport_mode', 'sea')->whereNull('vessel_name')->get()->each(function (Shipment $s) use ($vessels) {
            $s->forceFill([
                'vessel_name' => $vessels[mt_rand(0, count($vessels) - 1)],
                'container_number' => $this->letters(3).'U'.mt_rand(1000000, 9999999),
            ])->saveQuietly();
        });

        // 4. air + land demo shipments (only once)
        $operator = User::where('role', 'operator')->first() ?? User::where('role', 'admin')->first();
        $customers = User::where('role', 'customer')->get();
        if (! $operator || $customers->isEmpty() || Shipment::whereIn('transport_mode', ['air', 'land'])->exists()) {
            return;
        }

        $air = Carrier::firstOrCreate(['code' => 'ACE'], ['name' => 'AeroCargo Express', 'contact_email' => 'ace@carrier.test', 'is_active' => true]);
        $road = Carrier::firstOrCreate(['code' => 'RDL'], ['name' => 'RoadLink Logistics', 'contact_email' => 'rdl@carrier.test', 'is_active' => true]);

        $this->generate('air', [['cgk', 'sinA', 'dxb', 'ams'], ['sinA', 'dxb', 'fra'], ['kul', 'dxb', 'lhr'], ['hkg', 'dxb', 'ams'], ['cgk', 'hkg', 'fra']], 12, [14, 40], $loc, $operator, $customers, $air);
        $this->generate('land', [['jktW', 'bdg', 'smg', 'subW'], ['sinJ', 'kulH'], ['rtmH', 'antH', 'fraH'], ['hamH', 'fraH'], ['kulH', 'sinJ']], 12, [14, 60], $loc, $operator, $customers, $road);
    }

    private function generate(string $mode, array $routes, int $count, array $transitRange, array $loc, User $operator, $customers, Carrier $carrier): void
    {
        $shipments = app(ShipmentService::class);
        $tracking = app(TrackingService::class);
        $exceptions = app(ExceptionService::class);
        $cargo = ['Electronics', 'Pharmaceuticals', 'Fashion goods', 'Machinery parts', 'Perishables', 'Auto parts'];
        $airlines = ['Garuda Indonesia Cargo', 'Singapore Airlines Cargo', 'Emirates SkyCargo', 'Lufthansa Cargo', 'Cathay Cargo'];
        $drivers = ['Budi Santoso', 'Agus Pratama', 'Hendra Wijaya', 'Lars Vermeer', 'Jan de Vries', 'Ahmad Faizal'];

        for ($i = 0; $i < $count; $i++) {
            $keys = $routes[mt_rand(0, count($routes) - 1)];
            $route = array_map(fn ($k) => $loc[$k], $keys);
            $first = $route[0];
            $last = $route[count($route) - 1];
            $transit = mt_rand($transitRange[0], $transitRange[1]); // hours

            $r = mt_rand(1, 100);
            $delay = $r <= 65 ? mt_rand(-2, 4) : ($r <= 88 ? mt_rand(13, 20) : mt_rand(26, 40));
            $s = mt_rand(1, 100);
            $stage = $s <= 40 ? 'DELIVERED' : ($s <= 75 ? 'IN_TRANSIT' : ($s <= 83 ? 'PICKED_UP' : ($s <= 93 ? 'BOOKED' : 'CREATED')));

            $elapsed = 0;
            $hoursAgo = match ($stage) {
                'DELIVERED' => 8 + $transit + max($delay, 0) + mt_rand(2, 48),
                'IN_TRANSIT' => 8 + ($elapsed = mt_rand(2, max(3, $transit - 3))),
                'PICKED_UP' => 7,
                'BOOKED' => mt_rand(3, 24),
                default => mt_rand(1, 6),
            };
            $created = now()->subHours($hoursAgo)->startOfMinute();
            $expectedArrival = $created->copy()->addHours(8 + $transit);

            $extra = $mode === 'air'
                ? ['flight_number' => strtoupper($this->letters(2)).'-'.mt_rand(100, 999), 'airline' => $airlines[mt_rand(0, count($airlines) - 1)]]
                : ['vehicle_number' => 'B '.mt_rand(1000, 9999).' '.$this->letters(3), 'driver_name' => $drivers[mt_rand(0, count($drivers) - 1)]];

            $shipment = $shipments->create([
                'transport_mode' => $mode,
                'customer_id' => $customers->random()->id,
                'carrier_id' => $carrier->id,
                'origin_id' => $first->id,
                'destination_id' => $last->id,
                'cargo_type' => $cargo[mt_rand(0, count($cargo) - 1)],
                'weight_kg' => $mode === 'air' ? mt_rand(80, 3000) : mt_rand(500, 18000),
                'volume_cbm' => mt_rand(2, 40),
                'expected_departure' => $created->copy()->addHours(8),
                'expected_arrival' => $expectedArrival,
                'estimated_arrival' => $expectedArrival->copy()->addHours($delay),
            ] + $extra, $operator, $created);

            $ev = fn (string $type, Carbon $at, $l = null, ?string $desc = null, array $more = []) => $tracking->addEvent(
                $shipment,
                array_merge(['event_type' => $type, 'occurred_at' => $at, 'location_id' => $l?->id, 'description' => $desc], $more),
                $operator
            );

            if ($stage === 'CREATED') {
                continue;
            }
            $ev('BOOKED', $created->copy()->addHours(2), null, 'Booking confirmed.');
            if ($stage === 'BOOKED') {
                continue;
            }
            $ev('PICKED_UP', $created->copy()->addHours(6), $first, 'Cargo picked up.');
            if ($stage === 'PICKED_UP') {
                continue;
            }
            $depart = $mode === 'air' ? 'Flight departed.' : 'Truck departed.';
            $ev('IN_TRANSIT', $created->copy()->addHours(8), $first, $depart);
            $ev('DEPARTED_ORIGIN', $created->copy()->addHours(8), $first, 'Departed origin.');

            $deliverAt = $expectedArrival->copy()->addHours($delay);
            $step = max(3, intdiv($transit, 5));
            for ($k = 1; $k <= 8; $k++) {
                $h = $k * $step;
                $at = $created->copy()->addHours(8 + $h);
                $within = $stage === 'DELIVERED' ? $at->lt($deliverAt->copy()->subHours(14)) : ($h <= $elapsed);
                if (! $within || $h >= $transit) {
                    break;
                }
                if ($k === 2 && count($route) > 2) {
                    $ev('ARRIVED_AT_HUB', $at, $route[1], 'Arrived at transit hub.');
                    continue;
                }
                $p = $this->position($route, $h / $transit);
                $ev('LOCATION_UPDATE', $at, null, 'Position update.', ['location_name' => $p['name'], 'latitude' => $p['lat'], 'longitude' => $p['lng']]);
            }

            if ($transit >= 20 && mt_rand(1, 100) <= 25) {
                $detected = $created->copy()->addHours(8 + intdiv($transit, 3));
                $limit = $stage === 'DELIVERED' ? $deliverAt->copy()->subHours(16) : now()->subHour();
                if ($detected->lt($limit)) {
                    $types = \App\Enums\ExceptionType::values();
                    $ex = $exceptions->create($shipment, [
                        'type' => $types[mt_rand(0, count($types) - 1)], 'severity' => ['LOW', 'MEDIUM', 'HIGH'][mt_rand(0, 2)],
                        'description' => 'Reported by operations monitoring.', 'detected_at' => $detected,
                    ], $operator);
                    if ($stage === 'DELIVERED') {
                        $exceptions->update($ex, ['status' => 'RESOLVED', 'resolution_notes' => 'Resolved with partner.'], $operator, $detected->copy()->addHours(2));
                    } elseif (mt_rand(0, 1)) {
                        $exceptions->update($ex, ['status' => 'INVESTIGATING'], $operator);
                    }
                }
            }

            if ($stage === 'DELIVERED') {
                if ($first->country !== $last->country) {
                    $ev('CUSTOMS_CLEARANCE', $deliverAt->copy()->subHours(10), $last, 'Customs clearance completed.');
                }
                $ev('OUT_FOR_DELIVERY', $deliverAt->copy()->subHours(4), $last, 'Out for final delivery.');
                $ev('DELIVERED', $deliverAt, $last, 'Delivered and signed for.');
            }
        }
    }

    private function letters(int $n): string
    {
        $s = '';
        for ($i = 0; $i < $n; $i++) {
            $s .= chr(mt_rand(65, 90));
        }

        return $s;
    }

    private function position(array $locs, float $fraction): array
    {
        $n = count($locs) - 1;
        $pos = min(max($fraction, 0), 0.999) * $n;
        $i = (int) floor($pos);
        $t = $pos - $i;
        $a = $locs[$i];
        $b = $locs[$i + 1];

        return [
            'lat' => round($a->latitude + ($b->latitude - $a->latitude) * $t, 5),
            'lng' => round($a->longitude + ($b->longitude - $a->longitude) * $t, 5),
            'name' => "En route: {$a->city} → {$b->city}",
        ];
    }
}
