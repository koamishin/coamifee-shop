<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
final class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_name' => fake()->name(),
            'customer_id' => Customer::factory(),
            'order_type' => fake()->randomElement(['dine-in', 'takeout', 'delivery']),
            'payment_method' => fake()->randomElement(['cash', 'gcash', 'maya']),
            'total' => fake()->randomFloat(2, 5.00, 150.00),
            'status' => fake()->randomElement(['pending', 'confirmed', 'completed']),
            'table_number' => fake()->optional(0.7)->randomElement(['1', '2', '3', '4', '5', '6', '7', '8', '9', '10']),
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }
}
