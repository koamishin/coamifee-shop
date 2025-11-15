<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('order items store and display variant information', function () {
    $category = Category::factory()->create(['name' => 'Beverages']);

    $product = Product::factory()->create([
        'name' => 'Americano',
        'category_id' => $category->id,
        'price' => 100,
    ]);

    $hotVariant = ProductVariant::create([
        'product_id' => $product->id,
        'name' => 'Hot',
        'price' => 89,
        'is_default' => true,
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $coldVariant = ProductVariant::create([
        'product_id' => $product->id,
        'name' => 'Cold',
        'price' => 99,
        'is_default' => false,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $order = Order::factory()->create([
        'customer_name' => 'Test Customer',
        'order_type' => 'dine_in',
        'table_number' => 'Table 1',
        'subtotal' => 188,
        'total' => 188,
        'status' => 'pending',
        'payment_status' => 'unpaid',
    ]);

    // Create order item with hot variant
    $hotItem = OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_variant_id' => $hotVariant->id,
        'variant_name' => $hotVariant->name,
        'quantity' => 1,
        'price' => $hotVariant->price,
        'subtotal' => $hotVariant->price,
    ]);

    // Create order item with cold variant
    $coldItem = OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_variant_id' => $coldVariant->id,
        'variant_name' => $coldVariant->name,
        'quantity' => 1,
        'price' => $coldVariant->price,
        'subtotal' => $coldVariant->price,
    ]);

    // Verify the items are stored correctly
    expect($order->items)->toHaveCount(2);

    $refreshedHotItem = OrderItem::find($hotItem->id);
    expect($refreshedHotItem->variant_name)->toBe('Hot');
    expect($refreshedHotItem->product_variant_id)->toBe($hotVariant->id);
    expect((float) $refreshedHotItem->price)->toBe(89.0);

    $refreshedColdItem = OrderItem::find($coldItem->id);
    expect($refreshedColdItem->variant_name)->toBe('Cold');
    expect($refreshedColdItem->product_variant_id)->toBe($coldVariant->id);
    expect((float) $refreshedColdItem->price)->toBe(99.0);

    // Verify relationships work
    expect($refreshedHotItem->variant->name)->toBe('Hot');
    expect($refreshedColdItem->variant->name)->toBe('Cold');
});

test('order items without variants work correctly', function () {
    $product = Product::factory()->create([
        'name' => 'Regular Product',
        'price' => 150,
    ]);

    $order = Order::factory()->create([
        'customer_name' => 'Test Customer',
        'subtotal' => 150,
        'total' => 150,
    ]);

    $item = OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'price' => $product->price,
        'subtotal' => $product->price,
    ]);

    expect($item->variant_name)->toBeNull();
    expect($item->product_variant_id)->toBeNull();
    expect((float) $item->price)->toBe(150.0);
});
