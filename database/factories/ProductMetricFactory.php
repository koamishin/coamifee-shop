<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductMetric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductMetric>
 */
final class ProductMetricFactory extends Factory
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
            'metric_date' => fake()->date(),
            'orders_count' => fake()->numberBetween(0, 100),
            'total_revenue' => fake()->randomFloat(2, 0, 1000),
            'period_type' => fake()->randomElement(['daily', 'weekly', 'monthly']),
        ];
    }
}
