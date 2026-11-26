<?php

namespace Database\Factories;

use App\Enums\ShipmentStatus;
use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Shipment> */
class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    public function definition(): array
    {
        $departure = now()->addDay();
        $arrival = now()->addDays(21);

        return [
            'shipment_number' => 'SHP-'.now()->format('Y').'-'.str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'transport_mode' => 'sea',
            'customer_id' => User::factory(),
            'carrier_id' => Carrier::factory(),
            'origin_id' => Location::factory(),
            'destination_id' => Location::factory(),
            'cargo_type' => fake()->randomElement(['Electronics', 'Textiles', 'Machinery', 'Furniture', 'Food']),
            'weight_kg' => fake()->numberBetween(100, 20000),
            'volume_cbm' => fake()->numberBetween(1, 60),
            'expected_departure' => $departure,
            'expected_arrival' => $arrival,
            'estimated_arrival' => $arrival,
            'status' => ShipmentStatus::CREATED->value,
            'is_delayed' => false,
            'delay_hours' => 0,
        ];
    }

    public function status(ShipmentStatus $status): static
    {
        return $this->state(['status' => $status->value]);
    }
}
