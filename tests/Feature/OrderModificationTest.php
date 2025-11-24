<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\OrderModificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can add products to existing order', function () {
    // Create test data
    $category = Category::factory()->create();
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'price' => 10.00,
    ]);

    $order = Order::factory()->create([
        'status' => 'pending',
        'payment_status' => 'paid',
        'subtotal' => 50.00,
        'total' => 50.00,
        'paid_amount' => 50.00,
    ]);

    // Add initial order item
    $initialItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 5,
        'price' => 10.00,
    ]);

    $orderModificationService = app(OrderModificationService::class);

    // Add new product to order
    $result = $orderModificationService->addProductsToOrder($order, [
        [
            'product_id' => $product->id,
            'quantity' => 2,
        ],
    ]);

    expect($result['success'])->toBeTrue();
    expect($result['order'])->not->toBeNull();

    // Refresh order from database
    $order->refresh();

    // Check order status transitions
    expect($order->payment_status)->toBe('partially_paid');
    expect($order->inventory_processed)->toBeFalse();

    // Check that new item was added
    expect($order->items)->toHaveCount(2);

    // Check totals are recalculated correctly
    expect((float) $order->subtotal)->toBe(70.0); // 7 items * $10
    expect((float) $order->total)->toBe(70.0);
});

it('validates inventory constraints when adding products', function () {
    // Create a product
    $category = Category::factory()->create();
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'price' => 10.00,
    ]);

    $order = Order::factory()->create();

    $orderModificationService = app(OrderModificationService::class);

    // Test basic validation
    $result = $orderModificationService->addProductsToOrder($order, [
        [
            'product_id' => 999999, // Non-existent product
            'quantity' => 1,
        ],
    ]);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('No query results for model');
});

it('handles product variants correctly', function () {
    // Create test data with variants
    $category = Category::factory()->create();
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'price' => 10.00,
    ]);

    $variant = App\Models\ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price' => 12.00,
        'name' => 'Large',
    ]);

    $order = Order::factory()->create();

    $orderModificationService = app(OrderModificationService::class);

    // Add variant to order
    $result = $orderModificationService->addProductsToOrder($order, [
        [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 2,
        ],
    ]);

    expect($result['success'])->toBeTrue();

    // Check that order item includes variant information
    $order->refresh();
    $newItem = $order->items->last();

    expect($newItem->product_variant_id)->toBe($variant->id);
    expect($newItem->variant_name)->toBe('Large');
    expect((float) $newItem->price)->toBe(12.0);
    expect((float) $newItem->subtotal)->toBe(24.0); // 2 * $12
});
