<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Location> */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->streetName().' Terminal',
            'city' => fake()->city(),
            'country' => fake()->country(),
            'type' => 'PORT',
            'latitude' => fake()->latitude(-60, 60),
            'longitude' => fake()->longitude(),
        ];
    }
}
