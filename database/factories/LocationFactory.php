<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        return [
            'external_id' => $this->faker->unique()->randomNumber(5),
            'name' => $this->faker->city() . ' Dimension',
            'type' => $this->faker->randomElement(['Planet', 'Space station', 'Microverse']),
            'dimension' => $this->faker->word() . ' Dimension',
        ];
    }
}