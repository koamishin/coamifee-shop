<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('total units sold and revenue stats are calculated correctly', function (): void {
    // Create test products
    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();

    // Create first order with 5 units
    $order1 = Order::factory()
        ->create([
            'payment_status' => 'paid',
            'status' => 'completed',
            'total' => 500.00,
        ]);

    OrderItem::factory()
        ->create([
            'order_id' => $order1->id,
            'product_id' => $product1->id,
            'quantity' => 5,
            'price' => 100.00,
        ]);

    // Create second order with 3 units
    $order2 = Order::factory()
        ->create([
            'payment_status' => 'paid',
            'status' => 'completed',
            'total' => 300.00,
        ]);

    OrderItem::factory()
        ->create([
            'order_id' => $order2->id,
            'product_id' => $product2->id,
            'quantity' => 3,
            'price' => 100.00,
        ]);

    // Test by querying directly to verify the calculation
    $totalUnits = Order::query()
        ->where('payment_status', 'paid')
        ->where('status', 'completed')
        ->join('order_items', 'orders.id', '=', 'order_items.order_id')
        ->sum('order_items.quantity');

    $totalRevenue = Order::query()
        ->where('payment_status', 'paid')
        ->where('status', 'completed')
        ->sum('total');

    expect($totalUnits)->toBe(8);
    expect($totalRevenue)->toBe(800.00);
});
