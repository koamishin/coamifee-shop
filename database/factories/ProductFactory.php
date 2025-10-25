<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
final class ProductFactory extends Factory
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
                'Espresso',
                'Cappuccino',
                'Latte',
                'Americano',
                'Croissant',
                'Muffin',
                'Sandwich',
                'Salad',
                'Cheesecake',
                'Tiramisu',
                'Green Tea',
                'Black Tea',
                'Fruit Juice',
                'Smoothie',
            ]),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 2.50, 25.00),
            'category_id' => \App\Models\Category::factory(),
            'image_url' => fake()->imageUrl(400, 300, 'food'),
            'is_active' => fake()->boolean(95), // 95% chance of being active
            'sku' => fake()->unique()->bothify('PROD-####'),
            'preparation_time' => fake()->numberBetween(1, 30),
        ];
    }
}
