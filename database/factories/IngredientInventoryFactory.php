<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IngredientInventory>
 */
final class IngredientInventoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ingredient_id' => \App\Models\Ingredient::factory()->trackable(),
            'current_stock' => fake()->randomFloat(3, 0, 10000),
            'min_stock_level' => fake()->randomFloat(3, 100, 1000),
            'max_stock_level' => fake()->randomFloat(3, 1000, 50000),
            'location' => fake()->randomElement(['Main Storage', 'Fridge', 'Freezer', 'Pantry']),
            'last_restocked_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
