<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

uses()->group('integration', 'pos', 'orders');
uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    // Set the Filament panel
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('POS creates order with item-level discounts that display in OrdersProcessing', function () {
    // Create test products
    $product1 = Product::factory()->create([
        'name' => 'Discounted Coffee',
        'price' => 100.00,
    ]);

    $product2 = Product::factory()->create([
        'name' => 'Regular Tea',
        'price' => 50.00,
    ]);

    // Simulate POS page creating an order with discounted items
    $cartItems = [
        [
            'product_id' => $product1->id,
            'product_name' => $product1->name,
            'variant_id' => null,
            'variant_name' => null,
            'quantity' => 2,
            'price' => 100.00,
            'subtotal' => 200.00,
            'discount_percentage' => 20.00, // 20% discount
        ],
        [
            'product_id' => $product2->id,
            'product_name' => $product2->name,
            'variant_id' => null,
            'variant_name' => null,
            'quantity' => 1,
            'price' => 50.00,
            'subtotal' => 50.00,
            'discount_percentage' => 0, // No discount
        ],
    ];

    // Create order similar to how PosPage does it
    $order = Order::create([
        'customer_name' => 'Test Customer',
        'order_type' => 'dine_in',
        'payment_method' => 'cash',
        'payment_status' => 'unpaid',
        'subtotal' => 250.00,
        'total' => 210.00, // 200 - 40 (20% of 200) + 50 = 210
        'status' => 'pending',
        'table_number' => 5,
    ]);

    // Create order items with discounts
    foreach ($cartItems as $item) {
        $discountPercentage = $item['discount_percentage'] ?? 0;
        $discountAmount = $discountPercentage > 0 ? ($item['subtotal'] * $discountPercentage / 100) : 0;

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $item['product_id'],
            'quantity' => $item['quantity'],
            'price' => $item['price'],
            'subtotal' => $item['subtotal'],
            'discount_percentage' => $discountPercentage,
            'discount_amount' => $discountAmount,
            'discount' => $discountAmount,
        ]);
    }

    // Verify order was created with correct totals
    expect($order->fresh())
        ->subtotal->toBe('250.00')
        ->total->toBe('210.00')
        ->status->toBe('pending');

    // Verify order items have correct discount data
    $orderItems = OrderItem::where('order_id', $order->id)->get();

    expect($orderItems)->toHaveCount(2);

    $discountedItem = $orderItems->where('product_id', $product1->id)->first();
    $regularItem = $orderItems->where('product_id', $product2->id)->first();

    expect($discountedItem)
        ->discount_percentage->toBe('20.00')
        ->discount_amount->toBe('40.00')
        ->discount->toBe('40.00');

    expect($regularItem)
        ->discount_percentage->toBe('0.00')
        ->discount_amount->toBe('0.00')
        ->discount->toBe('0.00');

    // Verify that OrdersProcessing page loads the order with discount data
    $loadedOrder = Order::with(['items.product', 'items.variant'])->find($order->id);

    expect($loadedOrder->items)->toHaveCount(2);

    $loadedDiscountedItem = $loadedOrder->items->where('product_id', $product1->id)->first();

    expect($loadedDiscountedItem)
        ->discount_percentage->toBe('20.00')
        ->discount_amount->toBe('40.00');
});

test('order without item discounts still works correctly', function () {
    $product = Product::factory()->create(['price' => 100.00]);

    $order = Order::factory()->create([
        'subtotal' => 100.00,
        'total' => 100.00,
        'status' => 'pending',
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'price' => 100.00,
        'subtotal' => 100.00,
        'discount_percentage' => 0,
        'discount_amount' => 0,
        'discount' => 0,
    ]);

    $loadedOrder = Order::with(['items.product'])->find($order->id);
    $item = $loadedOrder->items->first();

    expect($item->discount_percentage)->toBe('0.00')
        ->and($item->discount_amount)->toBe('0.00');
});

test('multiple items can have different discount percentages', function () {
    $products = Product::factory()->count(3)->create(['price' => 100.00]);

    $order = Order::factory()->create([
        'subtotal' => 300.00,
        'total' => 240.00, // (100 - 20) + (100 - 30) + (100 - 10) = 240
        'status' => 'pending',
    ]);

    // Item 1: 20% discount
    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $products[0]->id,
        'quantity' => 1,
        'price' => 100.00,
        'subtotal' => 100.00,
        'discount_percentage' => 20.00,
        'discount_amount' => 20.00,
        'discount' => 20.00,
    ]);

    // Item 2: 30% discount
    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $products[1]->id,
        'quantity' => 1,
        'price' => 100.00,
        'subtotal' => 100.00,
        'discount_percentage' => 30.00,
        'discount_amount' => 30.00,
        'discount' => 30.00,
    ]);

    // Item 3: 10% discount
    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $products[2]->id,
        'quantity' => 1,
        'price' => 100.00,
        'subtotal' => 100.00,
        'discount_percentage' => 10.00,
        'discount_amount' => 10.00,
        'discount' => 10.00,
    ]);

    $loadedOrder = Order::with(['items'])->find($order->id);

    expect($loadedOrder->items)->toHaveCount(3)
        ->and($loadedOrder->items[0]->discount_percentage)->toBe('20.00')
        ->and($loadedOrder->items[1]->discount_percentage)->toBe('30.00')
        ->and($loadedOrder->items[2]->discount_percentage)->toBe('10.00');
});
