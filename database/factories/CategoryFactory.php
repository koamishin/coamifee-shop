<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
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
            'description' => fake()->sentence(),
            'is_active' => fake()->boolean(90), // 90% chance of being active
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }
}
