<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;

uses()->group('orders');

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('order item saves discount fields correctly', function () {
    $product = Product::factory()->create(['price' => 100.00]);

    $order = Order::factory()->create([
        'subtotal' => 100.00,
        'total' => 90.00,
        'status' => 'pending',
    ]);

    $orderItem = OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'price' => 100.00,
        'subtotal' => 100.00,
        'discount_percentage' => 10.00,
        'discount_amount' => 10.00,
        'discount' => 10.00,
    ]);

    $orderItem->refresh();

    expect($orderItem->discount_percentage)->toBe('10.00')
        ->and($orderItem->discount_amount)->toBe('10.00')
        ->and($orderItem->discount)->toBe('10.00');
});

test('order item discount is retrievable from database', function () {
    $product = Product::factory()->create(['price' => 50.00]);

    $order = Order::factory()->create([
        'subtotal' => 50.00,
        'total' => 37.50,
        'status' => 'pending',
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'price' => 50.00,
        'subtotal' => 50.00,
        'discount_percentage' => 25.00,
        'discount_amount' => 12.50,
        'discount' => 12.50,
    ]);

    $retrievedItem = OrderItem::where('order_id', $order->id)->first();

    expect($retrievedItem->discount_percentage)->toBe('25.00')
        ->and($retrievedItem->discount_amount)->toBe('12.50')
        ->and($retrievedItem->discount)->toBe('12.50');
});

test('multiple order items can have different discounts', function () {
    $product1 = Product::factory()->create(['price' => 100.00]);
    $product2 = Product::factory()->create(['price' => 200.00]);

    $order = Order::factory()->create([
        'subtotal' => 300.00,
        'total' => 270.00,
        'status' => 'pending',
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product1->id,
        'quantity' => 1,
        'price' => 100.00,
        'subtotal' => 100.00,
        'discount_percentage' => 10.00,
        'discount_amount' => 10.00,
        'discount' => 10.00,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product2->id,
        'quantity' => 1,
        'price' => 200.00,
        'subtotal' => 200.00,
        'discount_percentage' => 10.00,
        'discount_amount' => 20.00,
        'discount' => 20.00,
    ]);

    $items = OrderItem::where('order_id', $order->id)->get();

    expect($items)->toHaveCount(2)
        ->and($items[0]->discount_amount)->toBe('10.00')
        ->and($items[1]->discount_amount)->toBe('20.00');
});

test('order item without discount saves with zero values', function () {
    $product = Product::factory()->create(['price' => 100.00]);

    $order = Order::factory()->create([
        'subtotal' => 100.00,
        'total' => 100.00,
        'status' => 'pending',
    ]);

    $orderItem = OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'price' => 100.00,
        'subtotal' => 100.00,
        'discount_percentage' => 0,
        'discount_amount' => 0,
        'discount' => 0,
    ]);

    expect($orderItem->discount_percentage)->toBe('0.00')
        ->and($orderItem->discount_amount)->toBe('0.00')
        ->and($orderItem->discount)->toBe('0.00');
});
