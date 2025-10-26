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
        try {
            DB::beginTransaction();

            foreach ($order->items as $orderItem) {
                $this->processOrderItem($orderItem);
            }

            $order->update(['status' => 'completed']);

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

        foreach ($productIngredients as $productIngredient) {
            $ingredient = $productIngredient->ingredient;
            $quantityNeeded = $productIngredient->quantity_required * $orderItem->quantity;

            if ($ingredient->is_trackable) {
                $this->inventoryService->decreaseIngredientStock(
                    $ingredient,
                    $quantityNeeded,
                    $orderItem,
                    "Used in {$orderItem->quantity}x {$orderItem->product->name}"
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

        foreach ($productIngredients as $productIngredient) {
            $ingredient = $productIngredient->ingredient;

            if ($ingredient->is_trackable) {
                $quantityNeeded = $productIngredient->quantity_required * $orderItem->quantity;
                $inventory = $ingredient->inventory()->first();

                if (! $inventory || $inventory->current_stock < $quantityNeeded) {
                    return false;
                }
            }
        }

        return true;
    }
}
