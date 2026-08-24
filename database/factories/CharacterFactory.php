<?php

namespace Database\Factories;

use App\Models\Character;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

class CharacterFactory extends Factory
{
    protected $model = Character::class;

    public function definition(): array
    {
        return [
            'external_id' => $this->faker->unique()->randomNumber(5),
            'name' => $this->faker->name(),
            'status' => $this->faker->randomElement(['Alive', 'Dead', 'unknown']),
            'species' => $this->faker->randomElement(['Human', 'Alien', 'Humanoid', 'unknown']),
            'type' => $this->faker->word(),
            'gender' => $this->faker->randomElement(['Female', 'Male', 'Genderless', 'unknown']),
            'image' => $this->faker->imageUrl(),
            'origin_id' => Location::factory(),
            'location_id' => Location::factory(),
        ];
    }
}