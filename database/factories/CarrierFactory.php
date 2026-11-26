<?php

namespace Database\Factories;

use App\Models\Carrier;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Carrier> */
class CarrierFactory extends Factory
{
    protected $model = Carrier::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company().' Lines',
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'contact_email' => fake()->companyEmail(),
            'is_active' => true,
        ];
    }
}
