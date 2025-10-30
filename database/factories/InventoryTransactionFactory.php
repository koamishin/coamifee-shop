<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Ingredient;
use App\Models\InventoryTransaction;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryTransaction>
 */
final class InventoryTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $previousStock = fake()->randomFloat(3, 0, 10000);
        $quantityChange = fake()->randomFloat(3, -1000, 1000);

        return [
            'ingredient_id' => Ingredient::factory(),
            'transaction_type' => fake()->randomElement(['restock', 'usage', 'adjustment', 'waste']),
            'quantity_change' => $quantityChange,
            'previous_stock' => $previousStock,
            'new_stock' => max(0, $previousStock + $quantityChange),
            'reason' => fake()->sentence(),
            'order_item_id' => OrderItem::factory(),
        ];
    }
}
