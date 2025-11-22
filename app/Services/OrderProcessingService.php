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
use Illuminate\Support\Facades\Log;

final readonly class OrderProcessingService
{
    public function __construct(
        private InventoryService $inventoryService,
        private MetricsService $metricsService,
    ) {}

    public function processOrder(Order $order): bool
    {
        // Ensure order items are loaded
        if (! $order->relationLoaded('items')) {
            $order->load('items');
        }

        Log::info('Processing order', [
            'order_id' => $order->id,
            'inventory_processed' => $order->inventory_processed,
            'items_count' => $order->items->count(),
        ]);

        // Skip if inventory already processed
        if ($order->inventory_processed) {
            Log::info('Order already processed, skipping', ['order_id' => $order->id]);

            return true;
        }

        // Check if we have sufficient inventory before processing
        if (! $this->canFulfillOrder($order)) {
            Log::warning('Insufficient inventory for order', [
                'order_id' => $order->id,
                'order_items' => $order->items->map(fn ($item) => [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product?->name,
                    'quantity' => $item->quantity,
                ]),
            ]);

            return false;
        }

        try {
            DB::beginTransaction();

            Log::info('Starting inventory processing', ['order_id' => $order->id]);

            foreach ($order->items as $orderItem) {
                assert($orderItem instanceof OrderItem);
                $this->processOrderItem($orderItem);
            }

            $order->update([
                'inventory_processed' => true,
                'status' => 'completed',
            ]);

            DB::commit();

            Log::info('Order processing completed successfully', ['order_id' => $order->id]);

            return true;
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Failed to process order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    public function canFulfillOrder(Order $order): bool
    {
        Log::info('Checking if order can be fulfilled', ['order_id' => $order->id]);

        foreach ($order->items as $orderItem) {
            assert($orderItem instanceof OrderItem);
            if (! $this->canFulfillOrderItem($orderItem)) {
                Log::warning('Order item cannot be fulfilled', [
                    'order_id' => $order->id,
                    'order_item_id' => $orderItem->id,
                    'product_id' => $orderItem->product_id,
                    'product_name' => $orderItem->product?->name,
                ]);

                return false;
            }
        }

        Log::info('Order can be fulfilled', ['order_id' => $order->id]);

        return true;
    }

    private function processOrderItem(OrderItem $orderItem): void
    {
        $product = $orderItem->product;
        $productName = $product instanceof \App\Models\Product ? $product->name : 'Unknown Product';

        Log::info('Processing order item', [
            'order_item_id' => $orderItem->id,
            'product_id' => $orderItem->product_id,
            'product_name' => $productName,
            'quantity' => $orderItem->quantity,
        ]);

        $productIngredients = ProductIngredient::with('ingredient')
            ->where('product_id', $orderItem->product_id)
            ->get();

        if ($productIngredients->isEmpty()) {
            Log::info('No ingredients required for product', [
                'product_id' => $orderItem->product_id,
                'product_name' => $productName,
            ]);
        }

        // Process ingredients if they exist
        foreach ($productIngredients as $productIngredient) {
            $ingredient = $productIngredient->ingredient;
            if (! $ingredient instanceof Ingredient) {
                Log::warning('Invalid ingredient found', [
                    'product_ingredient_id' => $productIngredient->id,
                ]);

                continue;
            }

            $quantityNeeded = $productIngredient->quantity_required * $orderItem->quantity;

            Log::info('Processing ingredient', [
                'ingredient_id' => $ingredient->id,
                'ingredient_name' => $ingredient->name,
                'quantity_needed' => $quantityNeeded,
                'unit_type' => $ingredient->unit_type?->value,
            ]);

            $inventory = $ingredient->inventory()->first();
            if ($inventory) {
                $success = $this->inventoryService->decreaseIngredientStock(
                    $ingredient,
                    $quantityNeeded,
                    $orderItem,
                    "Used in {$orderItem->quantity}x {$productName}"
                );

                if (! $success) {
                    Log::error('Failed to decrease ingredient stock', [
                        'ingredient_id' => $ingredient->id,
                        'ingredient_name' => $ingredient->name,
                        'quantity_needed' => $quantityNeeded,
                        'current_stock' => $inventory->current_stock,
                    ]);

                    throw new Exception("Insufficient stock for ingredient: {$ingredient->name}");
                }

                Log::info('Successfully decreased ingredient stock', [
                    'ingredient_id' => $ingredient->id,
                    'ingredient_name' => $ingredient->name,
                    'quantity_deducted' => $quantityNeeded,
                ]);
            } else {
                Log::warning('No inventory tracking for ingredient', [
                    'ingredient_id' => $ingredient->id,
                    'ingredient_name' => $ingredient->name,
                ]);
            }

            $this->recordIngredientUsage($orderItem, $ingredient, $quantityNeeded);
        }

        // Record metrics - don't fail order processing if metrics fail
        try {
            $this->metricsService->recordProductMetrics($orderItem->product_id);
        } catch (Exception $e) {
            Log::error('Failed to record product metrics', [
                'product_id' => $orderItem->product_id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('Order item processed successfully', [
            'order_item_id' => $orderItem->id,
            'product_name' => $productName,
        ]);
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
        $product = $orderItem->product;
        $productName = $product instanceof \App\Models\Product ? $product->name : 'Unknown Product';

        $productIngredients = ProductIngredient::with('ingredient')
            ->where('product_id', $orderItem->product_id)
            ->get();

        // If product has no ingredients defined, allow the order
        if ($productIngredients->isEmpty()) {
            Log::info('Product has no ingredients, allowing order', [
                'product_id' => $orderItem->product_id,
                'product_name' => $productName,
            ]);

            return true;
        }

        foreach ($productIngredients as $productIngredient) {
            $ingredient = $productIngredient->ingredient;
            if (! $ingredient instanceof Ingredient) {
                Log::error('Invalid ingredient for product', [
                    'product_id' => $orderItem->product_id,
                    'product_ingredient_id' => $productIngredient->id,
                ]);

                return false;
            }

            $quantityNeeded = $productIngredient->quantity_required * $orderItem->quantity;
            $inventory = $ingredient->inventory()->first();

            if (! $inventory || ! $inventory instanceof \App\Models\IngredientInventory) {
                Log::warning('No inventory found for ingredient', [
                    'ingredient_id' => $ingredient->id,
                    'ingredient_name' => $ingredient->name,
                    'product_name' => $productName,
                ]);

                return false;
            }

            if ($inventory->current_stock < $quantityNeeded) {
                Log::warning('Insufficient stock for ingredient', [
                    'ingredient_id' => $ingredient->id,
                    'ingredient_name' => $ingredient->name,
                    'current_stock' => $inventory->current_stock,
                    'quantity_needed' => $quantityNeeded,
                    'product_name' => $productName,
                ]);

                return false;
            }

            Log::debug('Ingredient stock check passed', [
                'ingredient_id' => $ingredient->id,
                'ingredient_name' => $ingredient->name,
                'current_stock' => $inventory->current_stock,
                'quantity_needed' => $quantityNeeded,
            ]);
        }

        return true;
    }
}
