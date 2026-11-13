<?php

namespace Database\Factories;

use App\Models\Territory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Territory>
 */
class TerritoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->lexify('???')),
            'city' => $this->faker->city(),
            'country' => $this->faker->country(),
            'position' => 0,
            'is_active' => true,
        ];
    }
}
