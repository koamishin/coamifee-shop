<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IngredientUsage>
 */
final class IngredientUsageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_item_id' => \App\Models\OrderItem::factory(),
            'ingredient_id' => \App\Models\Ingredient::factory(),
            'quantity_used' => fake()->randomFloat(3, 1, 1000),
            'recorded_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
