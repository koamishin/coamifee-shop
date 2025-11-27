<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class OrderModificationService
{
    public function __construct(
        private PosService $posService,
        private OrderProcessingService $orderProcessingService,
    ) {}

    /**
     * Add products to an existing order
     *
     * @param  Order  $order  The order to modify
     * @param  array  $items  Array of items to add, each containing product_id, quantity, and optionally variant_id
     * @return array{success: bool, message: string, order: Order|null}
     */
    public function addProductsToOrder(Order $order, array $items): array
    {
        try {
            DB::beginTransaction();

            Log::info('Adding products to order', [
                'order_id' => $order->id,
                'items_to_add' => $items,
                'current_status' => $order->status,
                'payment_status' => $order->payment_status,
            ]);

            // Store original order state for comparison
            $originalSubtotal = (float) ($order->subtotal ?? 0);
            $originalPaymentStatus = $order->payment_status ?? 'unpaid';
            $originalInventoryProcessed = (bool) ($order->inventory_processed ?? false);

            // Validate and prepare new items
            $newItems = [];
            $additionalSubtotal = 0.0;

            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);

                // Check if product can be added (inventory check)
                if (! $this->posService->canAddToCart($product->id)) {
                    throw new Exception("Product '{$product->name}' is out of stock or unavailable");
                }

                // Handle variant if specified
                $variant = null;
                $price = $product->price;
                $variantName = null;

                if (! empty($item['variant_id'])) {
                    $variant = ProductVariant::find($item['variant_id']);
                    if (! $variant || $variant->product_id !== $product->id) {
                        throw new Exception("Invalid variant for product '{$product->name}'");
                    }
                    $price = $variant->price;
                    $variantName = $variant->name;
                }

                // Check inventory constraints
                $maxQuantity = $this->posService->getMaxProducibleQuantity($product->id);
                $currentOrderQuantity = $order->items()
                    ->where('product_id', $product->id)
                    ->where('product_variant_id', $item['variant_id'] ?? null)
                    ->sum('quantity');

                if ($currentOrderQuantity + $item['quantity'] > $maxQuantity) {
                    throw new Exception("Cannot add {$item['quantity']}x {$product->name}. Maximum available: {$maxQuantity}");
                }

                $itemSubtotal = $price * $item['quantity'];
                $additionalSubtotal += $itemSubtotal;

                $newItems[] = [
                    'product_id' => $product->id,
                    'product_variant_id' => $item['variant_id'] ?? null,
                    'variant_name' => $variantName,
                    'quantity' => $item['quantity'],
                    'price' => $price,
                    'subtotal' => $itemSubtotal,
                ];
            }

            // Create order items
            foreach ($newItems as $newItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $newItem['product_id'],
                    'product_variant_id' => $newItem['product_variant_id'],
                    'variant_name' => $newItem['variant_name'],
                    'quantity' => $newItem['quantity'],
                    'price' => $newItem['price'],
                    'is_served' => false, // New items are always unserved
                ]);
            }

            // Recalculate order totals
            $this->recalculateOrderTotals($order);

            // Handle order status transitions
            $this->handleOrderStatusTransitions($order, $originalSubtotal, $originalPaymentStatus, $originalInventoryProcessed);

            DB::commit();

            Log::info('Successfully added products to order', [
                'order_id' => $order->id,
                'items_added' => count($newItems),
                'new_total' => $order->total,
                'new_payment_status' => $order->payment_status,
            ]);

            return [
                'success' => true,
                'message' => 'Products added successfully',
                'order' => $order->fresh(['items.product', 'items.variant']),
            ];
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Failed to add products to order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'items_attempted' => $items,
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'order' => null,
            ];
        }
    }

    /**
     * Get available products that can be added to orders
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAvailableProducts()
    {
        return $this->posService->getActiveCategories()
            ->load('products.activeVariants')
            ->map(function ($category) {
                $category->products = $category->products->filter(function ($product) {
                    return $this->posService->canAddToCart($product->id);
                });

                return $category;
            });
    }

    /**
     * Check if a product can be added to an order
     */
    public function canAddProductToOrder(int $productId, ?int $variantId = null): bool
    {
        return $this->posService->canAddToCart($productId);
    }

    /**
     * Get maximum producible quantity for a product
     */
    public function getMaxProducibleQuantity(int $productId): int
    {
        return $this->posService->getMaxProducibleQuantity($productId);
    }

    /**
     * Recalculate order totals after adding items
     */
    private function recalculateOrderTotals(Order $order): void
    {
        // Reload items to get the updated list
        $order->load('items');

        // Calculate new subtotal from all items
        $newSubtotal = $order->items->sum(function ($item) {
            return $item->price * $item->quantity;
        });

        // Apply existing discount if any
        $discountAmount = 0.0;
        if ($order->discount_type && $order->discount_value) {
            $discountAmount = $newSubtotal * ($order->discount_value / 100);

            // Update discounted prices for all items
            $this->updateItemDiscountedPrices($order, $newSubtotal, (float) $order->discount_value / 100);
        }

        // Calculate final total
        $newTotal = $newSubtotal - $discountAmount + ($order->add_ons_total ?? 0);

        // Update order with new totals
        $order->update([
            'subtotal' => $newSubtotal,
            'discount_amount' => $discountAmount,
            'total' => $newTotal,
        ]);
    }

    /**
     * Handle order status transitions when items are added
     */
    private function handleOrderStatusTransitions(
        Order $order,
        float $originalSubtotal,
        string $originalPaymentStatus,
        bool $originalInventoryProcessed
    ): void {
        $newSubtotal = (float) $order->subtotal;
        $additionalAmount = $newSubtotal - $originalSubtotal;

        // If additional items were added (which they always are in this method)
        if ($additionalAmount > 0) {
            // Reset inventory processing flag since new items need to be processed
            $order->update(['inventory_processed' => false]);

            // If order was fully paid and new items are added, make it partially unpaid
            if ($originalPaymentStatus === 'paid') {
                // Mark as partially paid - some portion is already paid
                $order->update(['payment_status' => 'partially_paid']);

                Log::info('Order payment status changed to partially_paid', [
                    'order_id' => $order->id,
                    'original_paid_amount' => $order->paid_amount,
                    'new_total' => $order->total,
                ]);
            }

            // If order was completed, revert to pending since new items need preparation
            if ($order->status === 'completed') {
                $order->update(['status' => 'pending']);

                Log::info('Order status reverted to pending due to new items', [
                    'order_id' => $order->id,
                ]);
            }
        }
    }

    /**
     * Update discounted prices for all order items
     */
    private function updateItemDiscountedPrices(Order $order, float $totalSubtotal, float $discountPercentage): void
    {
        if ($totalSubtotal <= 0) {
            return;
        }

        foreach ($order->items as $orderItem) {
            $itemSubtotal = $orderItem->price * $orderItem->quantity;
            $itemDiscountAmount = $itemSubtotal * $discountPercentage;
            $discountedPrice = $orderItem->price - ($itemDiscountAmount / $orderItem->quantity);

            $orderItem->update([
                'discounted_price' => round($discountedPrice, 2),
            ]);
        }
    }
}
