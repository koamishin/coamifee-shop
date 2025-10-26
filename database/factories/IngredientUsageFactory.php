<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Ingredient;
use App\Models\IngredientUsage;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IngredientUsage>
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
            'order_item_id' => OrderItem::factory(),
            'ingredient_id' => Ingredient::factory(),
            'quantity_used' => fake()->randomFloat(3, 1, 1000),
            'recorded_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
