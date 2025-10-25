<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\OrderProcessingService;
use Exception;
use Illuminate\Support\Facades\DB;

final class PosCheckoutAction
{
    public function __construct(
        private OrderProcessingService $orderProcessingService,
    ) {}

    public function execute(array $cart, array $orderData): array
    {
        try {
            DB::beginTransaction();

            // Validate cart
            if (empty($cart)) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Cart is empty',
                ];
            }

            // Create the order
            $order = Order::create([
                'customer_name' => $orderData['customer_name'] ?? 'Guest',
                'customer_id' => $this->getCustomerId($orderData['customer_name'] ?? null),
                'order_type' => $orderData['order_type'] ?? 'dine-in',
                'payment_method' => $orderData['payment_method'] ?? 'cash',
                'table_number' => $orderData['table_number'] ?? null,
                'total' => $orderData['total'],
                'status' => 'pending',
                'notes' => $orderData['notes'] ?? null,
            ]);

            // Create order items
            foreach ($cart as $productId => $item) {
                $product = Product::find($productId);
                if (! $product) {
                    DB::rollBack();

                    return [
                        'success' => false,
                        'message' => "Product with ID {$productId} not found",
                    ];
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $productId,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            // Process the order with inventory deduction
            $order->load(['items.product']);
            $result = app(ProcessOrderAction::class)->execute($order);

            if (! $result['success']) {
                DB::rollBack();

                return [
                    'success' => false,
                    'message' => $result['message'],
                ];
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Order completed successfully',
                'order_id' => $order->id,
                'order_number' => $order->id,
                'total' => $order->total,
                'customer_name' => $order->customer_name,
                'order_type' => $order->order_type,
                'payment_method' => $order->payment_method,
                'table_number' => $order->table_number,
            ];

        } catch (Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Error processing order: '.$e->getMessage(),
            ];
        }
    }

    public function calculateOrderTotal(array $cart, float $taxRate = 0.0): array
    {
        $subtotal = 0.0;

        foreach ($cart as $item) {
            $subtotal += ($item['price'] * $item['quantity']);
        }

        $taxAmount = $subtotal * ($taxRate / 100);
        $total = $subtotal + $taxAmount;

        return [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
        ];
    }

    private function getCustomerId(?string $customerName): ?int
    {
        if (! $customerName || $customerName === 'Guest') {
            return null;
        }

        $customer = Customer::where('name', $customerName)->first();

        return $customer?->id;
    }
}
