<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\RefundLog;
use App\Models\User;
use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class RefundService
{
    public function verifyPin(User $user, string $pin): bool
    {
        if (! $user->admin_pin) {
            return false;
        }

        return $user->admin_pin === $pin;
    }

    /**
     * Check if an order can show the refund button
     */
    public function canShowRefundButton(Order $order): bool
    {
        // Hide refund button if order is completed AND paid
        if ($order->status === 'completed' && $order->payment_status === 'paid') {
            return false;
        }

        // Show refund button if:
        // 1. Order is paid and products are in progress (pending status)
        if ($order->payment_status === 'paid' && $order->status === 'pending') {
            return true;
        }

        // 2. Order has unpaid/unprocessed items (partially_paid status)
        if ($order->payment_status === 'partially_paid') {
            return true;
        }

        // 3. Order is completed but partially paid or unpaid
        if ($order->status === 'completed' && $order->payment_status !== 'paid') {
            return true;
        }

        return false;
    }

    /**
     * Get refundable items for an order
     *
     * @return array{items: array, total: float, type: string}
     */
    public function getRefundableItems(Order $order): array
    {
        $refundableItems = [];
        $refundTotal = 0.0;
        $refundType = 'full'; // full or partial

        // Load items if not loaded
        if (! $order->relationLoaded('items')) {
            $order->load('items');
        }

        // Case 1: Order is paid with items in progress - refund all items
        if ($order->payment_status === 'paid' && $order->status === 'pending') {
            foreach ($order->items as $item) {
                $itemSubtotal = $item->price * $item->quantity;
                $refundableItems[] = [
                    'order_item_id' => $item->id,
                    'product_name' => $item->product->name ?? 'Unknown Product',
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'subtotal' => $itemSubtotal,
                ];
                $refundTotal += $itemSubtotal;
            }
            $refundType = 'full';
        }
        // Case 2: Partially paid - refund only unpaid items
        elseif ($order->payment_status === 'partially_paid') {
            foreach ($order->items as $item) {
                // Items added after initial payment are considered unpaid
                $itemSubtotal = $item->price * $item->quantity;
                $refundableItems[] = [
                    'order_item_id' => $item->id,
                    'product_name' => $item->product->name ?? 'Unknown Product',
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'subtotal' => $itemSubtotal,
                ];
                $refundTotal += $itemSubtotal;
            }
            $refundType = 'partial';
        }
        // Case 3: Completed but not fully paid
        elseif ($order->status === 'completed' && $order->payment_status !== 'paid') {
            foreach ($order->items as $item) {
                $itemSubtotal = $item->price * $item->quantity;
                $refundableItems[] = [
                    'order_item_id' => $item->id,
                    'product_name' => $item->product->name ?? 'Unknown Product',
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'subtotal' => $itemSubtotal,
                ];
                $refundTotal += $itemSubtotal;
            }
            $refundType = 'partial';
        }

        return [
            'items' => $refundableItems,
            'total' => $refundTotal,
            'type' => $refundType,
        ];
    }

    /**
     * Process refund based on order status
     */
    public function processRefund(Order $order, Authenticatable|User $user, string $pin): array
    {
        // Verify the PIN
        if (! $this->verifyPin($user, $pin)) {
            return [
                'success' => false,
                'message' => 'Invalid PIN. Refund was not processed.',
            ];
        }

        // Check if order can be refunded
        if ($order->payment_status === 'unpaid') {
            return [
                'success' => false,
                'message' => 'Cannot refund an unpaid order.',
            ];
        }

        // Check if order is already refunded
        if ($order->payment_status === 'refunded') {
            return [
                'success' => false,
                'message' => 'This order has already been refunded.',
            ];
        }

        // Check if refund button should be hidden
        if (! $this->canShowRefundButton($order)) {
            return [
                'success' => false,
                'message' => 'Cannot refund a completed and fully paid order.',
            ];
        }

        try {
            DB::beginTransaction();

            $refundableData = $this->getRefundableItems($order);
            $refundType = $refundableData['type'];
            $refundAmount = $refundableData['total'];

            // Restore ingredients for refunded items
            $this->restoreIngredients($order, $refundableData['items']);

            // Determine the new payment status based on refund type
            if ($refundType === 'full') {
                // Full refund - mark as refunded
                $newPaymentStatus = 'refunded';
                $newStatus = 'refunded';
            } else {
                // Partial refund - mark as refund_partial
                $newPaymentStatus = 'refund_partial';
                $newStatus = $order->status; // Keep current status
            }

            // Update order status
            $order->update([
                'payment_status' => $newPaymentStatus,
                'status' => $newStatus,
            ]);

            // Log the refund with payment method details
            RefundLog::create([
                'order_id' => $order->id,
                'refunded_by' => $user->id,
                'refund_amount' => $refundAmount,
                'refund_type' => $refundType,
                'payment_method' => $order->payment_method,
                'refund_date' => now(),
            ]);

            DB::commit();

            Log::info('Order refunded', [
                'order_id' => $order->id,
                'refunded_by' => $user->id,
                'refund_amount' => $refundAmount,
                'refund_type' => $refundType,
                'payment_method' => $order->payment_method,
            ]);

            $refundTypeLabel = $refundType === 'full' ? 'Full refund' : 'Partial refund';

            return [
                'success' => true,
                'message' => "$refundTypeLabel for Order #{$order->id} processed successfully. Amount: {$refundAmount} ({$order->payment_method})",
            ];
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Refund processing failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'An error occurred while processing the refund. Please try again.',
            ];
        }
    }

    /**
     * Restore ingredients for refunded items
     * Only restores if the order inventory has been processed (i.e., inventory was deducted)
     */
    private function restoreIngredients(Order $order, array $refundableItems): void
    {
        // Only restore ingredients if inventory was already processed
        if (! $order->inventory_processed) {
            return;
        }

        $inventoryService = app(InventoryService::class);

        foreach ($refundableItems as $item) {
            $orderItem = $order->items()->find($item['order_item_id']);
            if (! $orderItem || ! $orderItem->product_id) {
                continue;
            }

            $product = $orderItem->product;
            if (! $product) {
                continue;
            }

            // Get all ingredients for this product
            $productIngredients = $product->ingredients()
                ->with('ingredient')
                ->get();

            // Restore each ingredient based on quantity ordered
            foreach ($productIngredients as $productIngredient) {
                $ingredient = $productIngredient->ingredient;
                if (! $ingredient) {
                    continue;
                }

                // Calculate quantity to restore (recipe quantity * order quantity)
                $quantityToRestore = $productIngredient->quantity_required * $item['quantity'];

                // Restore the ingredient
                $inventoryService->restockIngredient(
                    $ingredient,
                    $quantityToRestore,
                    "Refund: Order #{$order->id} - {$product->name} (x{$item['quantity']})"
                );
            }
        }
    }
}
