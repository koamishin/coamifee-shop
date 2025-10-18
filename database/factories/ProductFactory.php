<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
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
        $products = [
            'Espresso' => 2.50,
            'Cappuccino' => 4.25,
            'Latte' => 4.75,
            'Americano' => 3.25,
            'Macchiato' => 4.50,
            'Mocha' => 5.25,
            'Flat White' => 4.75,
            'Iced Coffee' => 3.50,
            'Iced Latte' => 5.25,
            'Green Tea' => 3.00,
            'Black Tea' => 2.75,
            'Croissant' => 3.50,
            'Muffin' => 3.25,
            'Bagel' => 3.00,
            'Sandwich' => 7.50,
            'Salad' => 8.25,
            'Cookie' => 2.50,
            'Brownie' => 3.75,
        ];

        $productName = fake()->randomElement(array_keys($products));
        $price = $products[$productName];

        return [
            'name' => $productName,
            'slug' => str($productName)->slug().'-'.fake()->unique()->randomNumber(3),
            'description' => fake()->sentence(10),
            'price' => $price,
            'cost' => $price * 0.3, // 30% cost
            'sku' => 'PROD-'.fake()->unique()->numerify('####'),
            'barcode' => fake()->ean13(),
            'category_id' => Category::factory(),
            'image_url' => 'https://picsum.photos/seed/'.str()->slug($productName).'/400/300.jpg',
            'is_active' => true,
            'is_featured' => fake()->boolean(30), // 30% chance of being featured
            'variations' => null,
            'ingredients' => implode(', ', fake()->words(5)),
            'preparation_time' => fake()->numberBetween(2, 15),
            'calories' => fake()->numberBetween(50, 500),
        ];
    }
}
