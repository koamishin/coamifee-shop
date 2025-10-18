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
        $categories = [
            'Coffee Drinks' => 'coffee',
            'Tea' => 'tea',
            'Pastries' => 'pastries',
            'Sandwiches' => 'sandwiches',
            'Snacks' => 'snacks',
            'Merchandise' => 'merchandise',
        ];

        $name = fake()->randomElement(array_keys($categories));
        $slug = $categories[$name].'-'.fake()->unique()->randomNumber(3);

        return [
            'name' => $name,
            'slug' => $slug,
            'description' => fake()->sentence(10),
            'icon' => fake()->randomElement(['coffee', 'mug-hot', 'cake-candles', 'bread-slice', 'cookie', 'tshirt']),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }
}
