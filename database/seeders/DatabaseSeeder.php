<?php

namespace Database\Seeders;

use App\Enums\ExceptionType;
use App\Enums\Role;
use App\Models\Carrier;
use App\Models\Location;
use App\Models\User;
use App\Services\ExceptionService;
use App\Services\ShipmentService;
use App\Services\TrackingService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Synthetic but consistent demo data (documented in docs/ATTRIBUTION.md).
 * Scenario "Group A": Southeast Asia -> Europe sea freight, delay threshold 12h.
 * Every record is created through the real services, so business rules, delay
 * calculation, notifications and audit logs are produced exactly like in production.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        mt_srand(2026); // deterministic demo data

        $admin = $this->user('Admin CargoTrace', 'admin@cargotrace.test', 'admin');
        $operator = $this->user('Olivia Operator', 'operator@cargotrace.test', 'operator');
        $customers = collect([
            ['Sarah Johnson', 'customer@cargotrace.test', 'Global Industries'],
            ['Tom Becker', 'tom@techsolutions.test', 'Tech Solutions'],
            ['Rina Putri', 'rina@retailhub.test', 'Retail Hub'],
            ['Alex Chen', 'alex@alphamfg.test', 'Alpha Manufacturing'],
            ['Omar Haddad', 'omar@omegaretail.test', 'Omega Retail'],
        ])->map(fn ($c) => $this->user($c[0], $c[1], 'customer', $c[2]));

        $carriers = collect([['FastShip', 'FSH'], ['CargoPro', 'CPR'], ['Logistics Partners', 'LGP']])
            ->map(fn ($c) => Carrier::firstOrCreate(['code' => $c[1]], ['name' => $c[0], 'contact_email' => strtolower($c[1]).'@carrier.test', 'is_active' => true]));

        $loc = [];
        foreach ([
            'jkt' => ['Tanjung Priok Port', 'Jakarta', 'Indonesia', -6.1050, 106.8800],
            'sub' => ['Tanjung Perak Port', 'Surabaya', 'Indonesia', -7.2000, 112.7300],
            'pkl' => ['Port Klang', 'Klang', 'Malaysia', 3.0000, 101.3900],
            'lcb' => ['Laem Chabang Port', 'Laem Chabang', 'Thailand', 13.0827, 100.8830],
            'sin' => ['Port of Singapore', 'Singapore', 'Singapore', 1.2644, 103.8400],
            'cmb' => ['Port of Colombo', 'Colombo', 'Sri Lanka', 6.9500, 79.8500],
            'jea' => ['Jebel Ali Port', 'Dubai', 'United Arab Emirates', 25.0100, 55.0600],
            'psd' => ['Port Said', 'Port Said', 'Egypt', 31.2653, 32.3019],
            'rtm' => ['Port of Rotterdam', 'Rotterdam', 'Netherlands', 51.9500, 4.1400],
            'ham' => ['Port of Hamburg', 'Hamburg', 'Germany', 53.5400, 9.9700],
            'ant' => ['Port of Antwerp', 'Antwerp', 'Belgium', 51.2600, 4.3900],
            'fxt' => ['Port of Felixstowe', 'Felixstowe', 'United Kingdom', 51.9600, 1.3500],
        ] as $key => [$name, $city, $country, $lat, $lng]) {
            $loc[$key] = Location::firstOrCreate(['name' => $name, 'city' => $city, 'country' => $country], ['type' => 'PORT', 'latitude' => $lat, 'longitude' => $lng]);
        }

        $shipments = app(ShipmentService::class);
        $tracking = app(TrackingService::class);
        $exceptions = app(ExceptionService::class);
        $cargo = ['Electronics', 'Textiles', 'Machinery parts', 'Furniture', 'Palm oil derivatives', 'Auto parts', 'Consumer goods'];

        for ($i = 0; $i < 40; $i++) {
            $origin = ['jkt', 'sub', 'pkl', 'lcb'][mt_rand(0, 3)];
            $dest = ['rtm', 'ham', 'ant', 'fxt'][mt_rand(0, 3)];
            $route = array_map(fn ($k) => $loc[$k], array_values(array_unique([$origin, 'sin', 'cmb', 'jea', 'psd', $dest])));
            $transitDays = mt_rand(18, 26);

            $r = mt_rand(1, 100);
            $delay = $r <= 60 ? mt_rand(-4, 8) : ($r <= 85 ? mt_rand(13, 22) : mt_rand(26, 50));

            $s = mt_rand(1, 100);
            $stage = $s <= 40 ? 'DELIVERED' : ($s <= 75 ? 'IN_TRANSIT' : ($s <= 82 ? 'PICKED_UP' : ($s <= 90 ? 'BOOKED' : ($s <= 96 ? 'CREATED' : 'CANCELLED'))));

            $elapsed = 0;
            $hoursAgo = match ($stage) {
                'DELIVERED' => 24 + $transitDays * 24 + max($delay, 0) + mt_rand(2, 120),
                'IN_TRANSIT' => 30 + ($elapsed = mt_rand(24, ($transitDays - 2) * 24)),
                'PICKED_UP' => 26 + mt_rand(0, 3),
                'BOOKED' => mt_rand(6, 48),
                'CANCELLED' => mt_rand(10, 60),
                default => mt_rand(1, 10),
            };
            $created = now()->subHours($hoursAgo)->startOfMinute();
            $expectedArrival = $created->copy()->addHours(24 + $transitDays * 24);

            $shipment = $shipments->create([
                'customer_id' => $customers->random()->id,
                'carrier_id' => $carriers->random()->id,
                'origin_id' => $loc[$origin]->id,
                'destination_id' => $loc[$dest]->id,
                'cargo_type' => $cargo[mt_rand(0, count($cargo) - 1)],
                'weight_kg' => mt_rand(800, 24000),
                'volume_cbm' => mt_rand(5, 66),
                'expected_departure' => $created->copy()->addHours(24),
                'expected_arrival' => $expectedArrival,
                'estimated_arrival' => $expectedArrival->copy()->addHours($delay),
                'items' => [['description' => 'Palletised cargo', 'quantity' => mt_rand(4, 40), 'weight_kg' => null]],
            ], $operator, $created);

            $ev = fn (string $type, Carbon $at, ?Location $l = null, ?string $desc = null, array $extra = []) => $tracking->addEvent(
                $shipment,
                array_merge(['event_type' => $type, 'occurred_at' => $at, 'location_id' => $l?->id, 'description' => $desc], $extra),
                $operator
            );

            if ($stage === 'CREATED') {
                continue;
            }
            $ev('BOOKED', $created->copy()->addHours(4), null, 'Booking confirmed with carrier.');
            if ($stage === 'BOOKED') {
                continue;
            }
            if ($stage === 'CANCELLED') {
                $ev('CANCELLED', $created->copy()->addHours(6), null, 'Cancelled by customer request.');
                continue;
            }
            $ev('PICKED_UP', $created->copy()->addHours(24), $loc[$origin], 'Cargo picked up at origin terminal.');
            if ($stage === 'PICKED_UP') {
                continue;
            }
            $ev('IN_TRANSIT', $created->copy()->addHours(30), $loc[$origin], 'Vessel departed.');
            $ev('DEPARTED_ORIGIN', $created->copy()->addHours(30), $loc[$origin], 'Departed origin port.');

            $deliverAt = $expectedArrival->copy()->addHours($delay);
            $transitHours = $transitDays * 24;
            for ($k = 1; $k <= 12; $k++) {
                $h = $k * 48;
                $at = $created->copy()->addHours(30 + $h);
                $within = $stage === 'DELIVERED' ? $at->lt($deliverAt->copy()->subHours(14)) : ($h <= $elapsed);
                if (! $within || $h >= $transitHours) {
                    break;
                }
                if ($k === 2) {
                    $ev('ARRIVED_AT_HUB', $at, $loc['sin'], 'Arrived at transit hub.');
                    continue;
                }
                $p = $this->position($route, $h / $transitHours);
                $ev('LOCATION_UPDATE', $at, null, 'Position update.', ['location_name' => $p['name'], 'latitude' => $p['lat'], 'longitude' => $p['lng']]);
            }

            // Operational exception on ~30% of moving/delivered shipments
            if (mt_rand(1, 100) <= 30) {
                $detected = $created->copy()->addHours(30 + mt_rand(48, max(49, $transitHours - 48)));
                $limit = $stage === 'DELIVERED' ? $deliverAt->copy()->subHours(16) : now()->subHour();
                if ($detected->lt($limit)) {
                    $types = ExceptionType::values();
                    $exception = $exceptions->create($shipment, [
                        'type' => $types[mt_rand(0, count($types) - 1)],
                        'severity' => ['LOW', 'MEDIUM', 'HIGH'][mt_rand(0, 2)],
                        'description' => 'Reported during transit monitoring.',
                        'detected_at' => $detected,
                    ], $operator);
                    $roll = mt_rand(1, 100);
                    if ($stage === 'DELIVERED' || $roll > 60) {
                        $resolvedAt = $detected->copy()->addHours(mt_rand(4, 60));
                        $cap = $stage === 'DELIVERED' ? $deliverAt->copy()->subHours(12) : now();
                        $resolvedAt = $resolvedAt->gt($cap) ? $cap : $resolvedAt;
                        $exceptions->update($exception, ['status' => 'RESOLVED', 'resolution_notes' => 'Cleared with carrier / authority.'], $operator, $resolvedAt);
                    } elseif ($roll > 30) {
                        $exceptions->update($exception, ['status' => 'INVESTIGATING'], $operator);
                    }
                }
            }

            if ($stage === 'DELIVERED') {
                $ev('CUSTOMS_CLEARANCE', $deliverAt->copy()->subHours(10), $loc[$dest], 'Customs clearance completed.');
                $ev('OUT_FOR_DELIVERY', $deliverAt->copy()->subHours(4), $loc[$dest], 'Out for final delivery.');
                $ev('DELIVERED', $deliverAt, $loc[$dest], 'Delivered and signed for.');
            }
        }

        // Transport modes (sea backfill + air/land demo shipments), usernames, flags.
        $this->call(ModeDemoSeeder::class);
    }

    private function user(string $name, string $email, string $role, ?string $company = null): User
    {
        $user = User::firstOrNew(['email' => $email]);
        $user->fill(['name' => $name, 'company' => $company, 'password' => 'password']);
        $user->username = strstr($email, '@', true);
        $user->role = Role::from($role);
        $user->save();

        return $user;
    }

    /** Interpolate a position along the route polyline for a progress fraction 0..1. */
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
            'name' => "At sea: {$a->city} → {$b->city}",
        ];
    }
}
