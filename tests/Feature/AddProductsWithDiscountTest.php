<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderModificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can add products with PWD discount', function () {
    // Create test data
    $category = Category::factory()->create();
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'price' => 100.00,
    ]);

    $order = Order::factory()->create([
        'status' => 'pending',
        'payment_status' => 'unpaid',
        'subtotal' => 0.00,
        'total' => 0.00,
    ]);

    $orderModificationService = app(OrderModificationService::class);

    // Add product with PWD discount (20%)
    $result = $orderModificationService->addProductsToOrder($order, [
        [
            'product_id' => $product->id,
            'quantity' => 1,
            'discount_type' => 'pwd',
            'discount_percentage' => 20.0,
        ],
    ]);

    expect($result['success'])->toBeTrue();

    // Refresh order from database
    $order->refresh();

    // Check that new item was added with discount
    expect($order->items)->toHaveCount(1);

    $item = $order->items->first();
    expect($item->discount_type)->toBe('pwd');
    expect((float) $item->discount_percentage)->toBe(20.0);
    expect((float) $item->discount_amount)->toBe(20.0); // 100 * 20%

    // Check order totals: subtotal=100, discount=20, total=80
    expect((float) $order->subtotal)->toBe(100.0);
    expect((float) $order->total)->toBe(80.0);
});

it('can add products with Senior citizen discount', function () {
    // Create test data
    $category = Category::factory()->create();
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'price' => 200.00,
    ]);

    $order = Order::factory()->create([
        'status' => 'pending',
        'payment_status' => 'unpaid',
        'subtotal' => 0.00,
        'total' => 0.00,
    ]);

    $orderModificationService = app(OrderModificationService::class);

    // Add product with Senior discount (20%)
    $result = $orderModificationService->addProductsToOrder($order, [
        [
            'product_id' => $product->id,
            'quantity' => 2,
            'discount_type' => 'senior',
            'discount_percentage' => 20.0,
        ],
    ]);

    expect($result['success'])->toBeTrue();

    // Refresh order from database
    $order->refresh();

    // Check that new item was added with discount
    expect($order->items)->toHaveCount(1);

    $item = $order->items->first();
    expect($item->discount_type)->toBe('senior');
    expect((float) $item->discount_percentage)->toBe(20.0);
    expect((float) $item->discount_amount)->toBe(80.0); // (200 * 2) * 20%

    // Check order totals: subtotal=400, discount=80, total=320
    expect((float) $order->subtotal)->toBe(400.0);
    expect((float) $order->total)->toBe(320.0);
});

it('can add multiple products with different discounts', function () {
    // Create test data
    $category = Category::factory()->create();
    $product1 = Product::factory()->create([
        'category_id' => $category->id,
        'price' => 100.00,
    ]);
    $product2 = Product::factory()->create([
        'category_id' => $category->id,
        'price' => 50.00,
    ]);

    $order = Order::factory()->create([
        'status' => 'pending',
        'payment_status' => 'unpaid',
        'subtotal' => 0.00,
        'total' => 0.00,
    ]);

    $orderModificationService = app(OrderModificationService::class);

    // Add products with different discounts
    $result = $orderModificationService->addProductsToOrder($order, [
        [
            'product_id' => $product1->id,
            'quantity' => 1,
            'discount_type' => 'pwd',
            'discount_percentage' => 20.0,
        ],
        [
            'product_id' => $product2->id,
            'quantity' => 1,
            'discount_type' => 'senior',
            'discount_percentage' => 20.0,
        ],
    ]);

    expect($result['success'])->toBeTrue();

    // Refresh order from database
    $order->refresh();

    // Check that both items were added
    expect($order->items)->toHaveCount(2);

    // Check first item
    $item1 = $order->items->where('product_id', $product1->id)->first();
    expect($item1->discount_type)->toBe('pwd');
    expect((float) $item1->discount_percentage)->toBe(20.0);
    expect((float) $item1->discount_amount)->toBe(20.0);

    // Check second item
    $item2 = $order->items->where('product_id', $product2->id)->first();
    expect($item2->discount_type)->toBe('senior');
    expect((float) $item2->discount_percentage)->toBe(20.0);
    expect((float) $item2->discount_amount)->toBe(10.0);

    // Check order totals: subtotal=150, total discount=30, total=120
    expect((float) $order->subtotal)->toBe(150.0);
    expect((float) $order->total)->toBe(120.0);
});

it('can add products without discount', function () {
    // Create test data
    $category = Category::factory()->create();
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'price' => 100.00,
    ]);

    $order = Order::factory()->create([
        'status' => 'pending',
        'payment_status' => 'unpaid',
        'subtotal' => 0.00,
        'total' => 0.00,
    ]);

    $orderModificationService = app(OrderModificationService::class);

    // Add product without discount
    $result = $orderModificationService->addProductsToOrder($order, [
        [
            'product_id' => $product->id,
            'quantity' => 1,
        ],
    ]);

    expect($result['success'])->toBeTrue();

    // Refresh order from database
    $order->refresh();

    // Check that new item was added without discount
    expect($order->items)->toHaveCount(1);

    $item = $order->items->first();
    expect($item->discount_type)->toBeNull();
    expect((float) ($item->discount_percentage ?? 0))->toBe(0.0);
    expect((float) ($item->discount_amount ?? 0))->toBe(0.0);

    // Check order totals
    expect((float) $order->subtotal)->toBe(100.0);
    expect((float) $order->total)->toBe(100.0);
});
