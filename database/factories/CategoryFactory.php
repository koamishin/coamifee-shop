<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
final class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'Coffee',
                'Tea',
                'Pastries',
                'Sandwiches',
                'Salads',
                'Desserts',
                'Beverages',
                'Breakfast',
            ]),
            'icon' => fake()->randomElement([
                'heroicon-o-cake',
                'heroicon-o-shopping-bag',
                'heroicon-o-star',
                'heroicon-o-heart',
                'heroicon-o-sparkles',
            ]),
            'description' => fake()->sentence(),
            'is_active' => fake()->boolean(90), // 90% chance of being active
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }
}
