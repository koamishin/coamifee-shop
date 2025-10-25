<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ingredient>
 */
final class IngredientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'unit_type' => fake()->randomElement(['grams', 'ml', 'pieces', 'liters', 'kilograms']),
            'is_trackable' => fake()->boolean(70), // 70% chance of being trackable
            'current_stock' => fake()->numberBetween(0, 10000),
            'unit_cost' => fake()->randomFloat(2, 0.001, 0.10),
            'supplier' => fake()->company(),
        ];
    }

    public function trackable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_trackable' => true,
        ]);
    }

    public function untrackable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_trackable' => false,
        ]);
    }
}
