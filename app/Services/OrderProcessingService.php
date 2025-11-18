<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Ingredient;
use App\Models\IngredientUsage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductIngredient;
use Exception;
use Illuminate\Support\Facades\DB;

final readonly class OrderProcessingService
{
    public function __construct(
        private InventoryService $inventoryService,
        private MetricsService $metricsService,
    ) {}

    public function processOrder(Order $order): bool
    {
        // Skip if inventory already processed
        if ($order->inventory_processed) {
            return true;
        }

        // Check if we have sufficient inventory before processing
        if (! $this->canFulfillOrder($order)) {
            return false;
        }

        try {
            DB::beginTransaction();

            foreach ($order->items as $orderItem) {
                assert($orderItem instanceof OrderItem);
                $this->processOrderItem($orderItem);
            }

            $order->update([
                'status' => 'completed',
                'inventory_processed' => true,
            ]);

            DB::commit();

            return true;
        } catch (Exception) {
            DB::rollBack();

            return false;
        }
    }

    public function canFulfillOrder(Order $order): bool
    {
        foreach ($order->items as $orderItem) {
            assert($orderItem instanceof OrderItem);
            if (! $this->canFulfillOrderItem($orderItem)) {
                return false;
            }
        }

        return true;
    }

    private function processOrderItem(OrderItem $orderItem): void
    {
        $productIngredients = ProductIngredient::with('ingredient')
            ->where('product_id', $orderItem->product_id)
            ->get();

        // Process ingredients if they exist
        foreach ($productIngredients as $productIngredient) {
            $ingredient = $productIngredient->ingredient;
            if (! $ingredient instanceof Ingredient) {
                continue;
            }

            $quantityNeeded = $productIngredient->quantity_required * $orderItem->quantity;

            $inventory = $ingredient->inventory()->first();
            if ($inventory) {
                $product = $orderItem->product;
                $productName = $product instanceof \App\Models\Product ? $product->name : 'Unknown Product';

                $this->inventoryService->decreaseIngredientStock(
                    $ingredient,
                    $quantityNeeded,
                    $orderItem,
                    "Used in {$orderItem->quantity}x {$productName}"
                );
            }

            $this->recordIngredientUsage($orderItem, $ingredient, $quantityNeeded);
        }

        $this->metricsService->recordProductMetrics($orderItem->product_id);
    }

    private function recordIngredientUsage(OrderItem $orderItem, Ingredient $ingredient, float $quantity): void
    {
        IngredientUsage::query()->create([
            'order_item_id' => $orderItem->id,
            'ingredient_id' => $ingredient->id,
            'quantity_used' => $quantity,
            'recorded_at' => now(),
        ]);
    }

    private function canFulfillOrderItem(OrderItem $orderItem): bool
    {
        $productIngredients = ProductIngredient::with('ingredient')
            ->where('product_id', $orderItem->product_id)
            ->get();

        // If product has no ingredients defined, allow the order
        if ($productIngredients->isEmpty()) {
            return true;
        }

        foreach ($productIngredients as $productIngredient) {
            $ingredient = $productIngredient->ingredient;
            if (! $ingredient instanceof Ingredient) {
                return false;
            }

            $quantityNeeded = $productIngredient->quantity_required * $orderItem->quantity;
            $inventory = $ingredient->inventory()->first();

            if (! $inventory || ! $inventory instanceof \App\Models\IngredientInventory || $inventory->current_stock < $quantityNeeded) {
                return false;
            }
        }

        return true;
    }
}
