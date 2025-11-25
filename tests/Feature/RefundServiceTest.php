<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->refundService = app(RefundService::class);
    $this->user = User::factory()->create(['admin_pin' => '1234']);
});

describe('RefundService', function () {
    describe('canShowRefundButton', function () {
        test('hides refund button for completed and fully paid orders', function () {
            $order = Order::factory()->create([
                'status' => 'completed',
                'payment_status' => 'paid',
            ]);

            expect($this->refundService->canShowRefundButton($order))->toBeFalse();
        });

        test('shows refund button for paid orders in progress', function () {
            $order = Order::factory()->create([
                'status' => 'pending',
                'payment_status' => 'paid',
            ]);

            expect($this->refundService->canShowRefundButton($order))->toBeTrue();
        });

        test('shows refund button for partially paid orders', function () {
            $order = Order::factory()->create([
                'status' => 'completed',
                'payment_status' => 'partially_paid',
            ]);

            expect($this->refundService->canShowRefundButton($order))->toBeTrue();
        });

        test('shows refund button for completed unpaid orders', function () {
            $order = Order::factory()->create([
                'status' => 'completed',
                'payment_status' => 'unpaid',
            ]);

            expect($this->refundService->canShowRefundButton($order))->toBeTrue();
        });

        test('does not show refund button for unpaid orders', function () {
            $order = Order::factory()->create([
                'status' => 'pending',
                'payment_status' => 'unpaid',
            ]);

            expect($this->refundService->canShowRefundButton($order))->toBeFalse();
        });
    });

    describe('getRefundableItems', function () {
        test('returns all items as full refund for paid pending orders', function () {
            $product = Product::factory()->create(['price' => 100.00]);
            $order = Order::factory()->create([
                'status' => 'pending',
                'payment_status' => 'paid',
            ]);
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => 2,
                'price' => 100.00,
            ]);

            $refundData = $this->refundService->getRefundableItems($order);

            expect($refundData['type'])->toBe('full');
            expect($refundData['total'])->toBe(200.0);
            expect(count($refundData['items']))->toBe(1);
        });

        test('returns items as partial refund for partially paid orders', function () {
            $product = Product::factory()->create(['price' => 150.00]);
            $order = Order::factory()->create([
                'status' => 'completed',
                'payment_status' => 'partially_paid',
            ]);
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'price' => 150.00,
            ]);

            $refundData = $this->refundService->getRefundableItems($order);

            expect($refundData['type'])->toBe('partial');
            expect($refundData['total'])->toBe(150.0);
        });

        test('calculates correct total for multiple items', function () {
            $product1 = Product::factory()->create(['price' => 100.00]);
            $product2 = Product::factory()->create(['price' => 50.00]);
            $order = Order::factory()->create([
                'status' => 'pending',
                'payment_status' => 'paid',
            ]);
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'product_id' => $product1->id,
                'quantity' => 2,
                'price' => 100.00,
            ]);
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'product_id' => $product2->id,
                'quantity' => 3,
                'price' => 50.00,
            ]);

            $refundData = $this->refundService->getRefundableItems($order);

            expect($refundData['total'])->toBe(350.0);
            expect(count($refundData['items']))->toBe(2);
        });
    });

    describe('processRefund', function () {
        test('fails with invalid pin', function () {
            $order = Order::factory()->create([
                'status' => 'pending',
                'payment_status' => 'paid',
            ]);

            $result = $this->refundService->processRefund($order, $this->user, '9999');

            expect($result['success'])->toBeFalse();
            expect($result['message'])->toContain('Invalid PIN');
        });

        test('fails for unpaid orders', function () {
            $order = Order::factory()->create(['payment_status' => 'unpaid']);

            $result = $this->refundService->processRefund($order, $this->user, '1234');

            expect($result['success'])->toBeFalse();
            expect($result['message'])->toContain('Cannot refund an unpaid order');
        });

        test('fails for already refunded orders', function () {
            $order = Order::factory()->create(['payment_status' => 'refunded']);

            $result = $this->refundService->processRefund($order, $this->user, '1234');

            expect($result['success'])->toBeFalse();
            expect($result['message'])->toContain('already been refunded');
        });

        test('fails for completed paid orders', function () {
            $order = Order::factory()->create([
                'status' => 'completed',
                'payment_status' => 'paid',
            ]);

            $result = $this->refundService->processRefund($order, $this->user, '1234');

            expect($result['success'])->toBeFalse();
            expect($result['message'])->toContain('Cannot refund a completed and fully paid order');
        });

        test('processes full refund for paid pending orders', function () {
            $product = Product::factory()->create(['price' => 100.00]);
            $order = Order::factory()->create([
                'status' => 'pending',
                'payment_status' => 'paid',
            ]);
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'price' => 100.00,
            ]);

            $result = $this->refundService->processRefund($order, $this->user, '1234');

            expect($result['success'])->toBeTrue();
            expect($result['message'])->toContain('Full refund');

            $order->refresh();
            expect($order->payment_status)->toBe('refunded');
            expect($order->status)->toBe('refunded');
        });

        test('processes partial refund for partially paid orders', function () {
            $product = Product::factory()->create(['price' => 100.00]);
            $order = Order::factory()->create([
                'status' => 'completed',
                'payment_status' => 'partially_paid',
            ]);
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'price' => 100.00,
            ]);

            $result = $this->refundService->processRefund($order, $this->user, '1234');

            expect($result['success'])->toBeTrue();
            expect($result['message'])->toContain('Partial refund');

            $order->refresh();
            expect($order->payment_status)->toBe('refund_partial');
            expect($order->status)->toBe('completed');
        });

        test('creates refund log with correct type', function () {
            $product = Product::factory()->create(['price' => 100.00]);
            $order = Order::factory()->create([
                'status' => 'pending',
                'payment_status' => 'paid',
                'payment_method' => 'cash',
            ]);
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'price' => 100.00,
            ]);

            $this->refundService->processRefund($order, $this->user, '1234');

            $refundLog = App\Models\RefundLog::where('order_id', $order->id)->first();
            expect($refundLog)->not->toBeNull();
            expect($refundLog->refund_type)->toBe('full');
            expect((float) $refundLog->refund_amount)->toBe(100.0);
        });

        test('restores ingredients when refunding an order with processed inventory', function () {
            // Create ingredient and product with ingredient relationship
            $ingredient = App\Models\Ingredient::factory()->create();
            $ingredientInventory = App\Models\IngredientInventory::factory()->create([
                'ingredient_id' => $ingredient->id,
                'current_stock' => 5,
            ]);

            $product = Product::factory()->create(['price' => 100.00]);
            App\Models\ProductIngredient::factory()->create([
                'product_id' => $product->id,
                'ingredient_id' => $ingredient->id,
                'quantity_required' => 1.0,
            ]);

            // Create order with inventory already processed (paid pending order with inventory deducted)
            $order = Order::factory()->create([
                'status' => 'pending',
                'payment_status' => 'paid',
                'inventory_processed' => true,
            ]);
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => 2,
                'price' => 100.00,
            ]);

            // Manually deduct inventory to simulate the state
            $ingredientInventory->update(['current_stock' => 3]);

            // Process refund
            $result = $this->refundService->processRefund($order, $this->user, '1234');

            expect($result['success'])->toBeTrue();

            // Check that inventory was restored
            $ingredientInventory->refresh();
            expect((float) $ingredientInventory->current_stock)->toBe(5.0);
        });
    });
});
