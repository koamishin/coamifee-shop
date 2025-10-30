<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Ingredient;
use App\Models\IngredientInventory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IngredientInventory>
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
            'ingredient_id' => Ingredient::factory(),
            'current_stock' => fake()->randomFloat(3, 0, 10000),
            'min_stock_level' => fake()->randomFloat(3, 100, 1000),
            'max_stock_level' => fake()->randomFloat(3, 1000, 50000),
            'location' => fake()->randomElement(['Main Storage', 'Fridge', 'Freezer', 'Pantry']),
            'last_restocked_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
