<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Order;
use App\Services\InventoryService;
use Illuminate\Support\Facades\Log;

final class OrderObserver
{
    public function __construct(
        private readonly InventoryService $inventoryService
    ) {}

    /**
     * Handle the Order "created" event.
     * Automatically deduct inventory when an order is created.
     */
    public function created(Order $order): void
    {
        // Load the order items with their products
        $order->load('items.product');

        foreach ($order->items as $orderItem) {
            $product = $orderItem->product;
            $quantity = $orderItem->quantity;

            // Deduct inventory for each product in the order
            $success = $this->inventoryService->deductInventoryForProduct(
                $product,
                $quantity,
                $orderItem
            );

            if (! $success) {
                Log::warning("Failed to deduct inventory for order {$order->id}, product {$product->id}");
            }
        }
    }
}
