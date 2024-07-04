<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code'   => fake()->numberBetween(10000, 90000),
            'name'   => fake()->sentence(2),
            'status' => fake()->randomElement(['Active', 'Inactive', 'Pending']),
            'price'  => fake()->randomFloat(2, 0, 1000),
            'address' => [
                'line1'   => fake()->buildingNumber(),
                'line2'   => fake()->streetName(),
                'city'    => fake()->city(),
                'state'   => fake()->state(),
                'country' => fake()->country(),
            ],
        ];
    }
}
