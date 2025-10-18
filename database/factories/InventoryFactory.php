<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Inventory>
 */
final class InventoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quantity' => fake()->numberBetween(10, 200),
            'minimum_stock' => fake()->numberBetween(5, 25),
            'maximum_stock' => fake()->numberBetween(100, 500),
            'unit_cost' => fake()->randomFloat(2, 0.50, 15.00),
            'location' => fake()->randomElement(['Main Storage', 'Front Counter', 'Back Room', 'Walk-in Cooler']),
            'notes' => fake()->boolean(30) ? fake()->sentence() : null,
        ];
    }
}
