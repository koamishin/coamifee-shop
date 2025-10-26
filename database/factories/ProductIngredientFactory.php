<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Ingredient;
use App\Models\Product;
use App\Models\ProductIngredient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductIngredient>
 */
final class ProductIngredientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'ingredient_id' => Ingredient::factory(),
            'quantity_required' => fake()->randomFloat(3, 1, 1000),
        ];
    }
}
