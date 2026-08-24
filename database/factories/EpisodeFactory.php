<?php

namespace Database\Factories;

use App\Models\Episode;
use Illuminate\Database\Eloquent\Factories\Factory;

class EpisodeFactory extends Factory
{
    protected $model = Episode::class;

    public function definition(): array
    {
        return [
            'external_id' => $this->faker->unique()->randomNumber(5),
            'name' => $this->faker->sentence(3),
            'air_date' => $this->faker->date('F j, Y'), // Ejemplo: "December 2, 2013"
            'episode_code' => 'S' . $this->faker->numberBetween(1, 5) . 'E' . $this->faker->numberBetween(1, 10), // Ejemplo: "S01E01"
        ];
    }
}