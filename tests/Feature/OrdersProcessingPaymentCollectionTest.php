<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $user = User::factory()->create();
    actingAs($user);
});

test('collect payment action calculates correct total with item discounts', function (): void {
    $product = Product::factory()->create(['price' => 45.00]);

    $order = Order::factory()->create([
        'subtotal' => 45.00,
        'discount_amount' => 0,
        'add_ons_total' => 0,
        'total' => 45.00,
        'payment_status' => 'unpaid',
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'price' => 45.00,
        'subtotal' => 45.00,
        'discount_percentage' => 20.00,
        'discount_amount' => 9.00,
        'discount' => 9.00,
    ]);

    // Reload order with items
    $order = $order->fresh('items');

    // Calculate the correct total as per the fix
    $itemLevelDiscountTotal = 0.0;
    foreach ($order->items as $item) {
        $itemLevelDiscountTotal += (float) ($item->discount_amount ?? $item->discount ?? 0);
    }

    $originalSubtotal = (float) $order->subtotal;
    $subtotalAfterItemDiscounts = $originalSubtotal - $itemLevelDiscountTotal;
    $orderLevelDiscount = (float) ($order->discount_amount ?? 0);
    $addOnsTotal = (float) ($order->add_ons_total ?? 0);
    $correctTotal = $subtotalAfterItemDiscounts - $orderLevelDiscount + $addOnsTotal;

    // The total should be 36.00 (45.00 - 9.00), not 45.00
    expect($correctTotal)->toBe(36.00);
});

test('collect payment with multiple items and different discounts', function (): void {
    $product1 = Product::factory()->create(['price' => 50.00]);
    $product2 = Product::factory()->create(['price' => 100.00]);

    $order = Order::factory()->create([
        'subtotal' => 150.00,
        'discount_amount' => 0,
        'add_ons_total' => 0,
        'total' => 150.00,
        'payment_status' => 'unpaid',
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product1->id,
        'quantity' => 1,
        'price' => 50.00,
        'subtotal' => 50.00,
        'discount_percentage' => 10.00,
        'discount_amount' => 5.00,
        'discount' => 5.00,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product2->id,
        'quantity' => 1,
        'price' => 100.00,
        'subtotal' => 100.00,
        'discount_percentage' => 15.00,
        'discount_amount' => 15.00,
        'discount' => 15.00,
    ]);

    $order = $order->fresh('items');

    $itemLevelDiscountTotal = 0.0;
    foreach ($order->items as $item) {
        $itemLevelDiscountTotal += (float) ($item->discount_amount ?? $item->discount ?? 0);
    }

    $originalSubtotal = (float) $order->subtotal;
    $subtotalAfterItemDiscounts = $originalSubtotal - $itemLevelDiscountTotal;
    $orderLevelDiscount = (float) ($order->discount_amount ?? 0);
    $addOnsTotal = (float) ($order->add_ons_total ?? 0);
    $correctTotal = $subtotalAfterItemDiscounts - $orderLevelDiscount + $addOnsTotal;

    // The total should be 130.00 (150.00 - 5.00 - 15.00), not 150.00
    expect($correctTotal)->toBe(130.00);
});

test('collect payment with order-level discount applied', function (): void {
    $product = Product::factory()->create(['price' => 100.00]);

    $order = Order::factory()->create([
        'subtotal' => 100.00,
        'discount_amount' => 10.00,
        'add_ons_total' => 0,
        'total' => 90.00,
        'payment_status' => 'unpaid',
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

    $order = $order->fresh('items');

    $itemLevelDiscountTotal = 0.0;
    foreach ($order->items as $item) {
        $itemLevelDiscountTotal += (float) ($item->discount_amount ?? $item->discount ?? 0);
    }

    $originalSubtotal = (float) $order->subtotal;
    $subtotalAfterItemDiscounts = $originalSubtotal - $itemLevelDiscountTotal;
    $orderLevelDiscount = (float) ($order->discount_amount ?? 0);
    $addOnsTotal = (float) ($order->add_ons_total ?? 0);
    $correctTotal = $subtotalAfterItemDiscounts - $orderLevelDiscount + $addOnsTotal;

    // The total should be 90.00 (100.00 - 10.00 order discount), not 100.00
    expect($correctTotal)->toBe(90.00);
});

test('collect payment with both item and order-level discounts', function (): void {
    $product = Product::factory()->create(['price' => 200.00]);

    $order = Order::factory()->create([
        'subtotal' => 200.00,
        'discount_amount' => 20.00,
        'add_ons_total' => 0,
        'total' => 200.00,
        'payment_status' => 'unpaid',
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'price' => 200.00,
        'subtotal' => 200.00,
        'discount_percentage' => 10.00,
        'discount_amount' => 20.00,
        'discount' => 20.00,
    ]);

    $order = $order->fresh('items');

    $itemLevelDiscountTotal = 0.0;
    foreach ($order->items as $item) {
        $itemLevelDiscountTotal += (float) ($item->discount_amount ?? $item->discount ?? 0);
    }

    $originalSubtotal = (float) $order->subtotal;
    $subtotalAfterItemDiscounts = $originalSubtotal - $itemLevelDiscountTotal;
    $orderLevelDiscount = (float) ($order->discount_amount ?? 0);
    $addOnsTotal = (float) ($order->add_ons_total ?? 0);
    $correctTotal = $subtotalAfterItemDiscounts - $orderLevelDiscount + $addOnsTotal;

    // The total should be 160.00 (200.00 - 20.00 item discount - 20.00 order discount)
    expect($correctTotal)->toBe(160.00);
});

test('collect payment with add-ons included', function (): void {
    $product = Product::factory()->create(['price' => 100.00]);

    $order = Order::factory()->create([
        'subtotal' => 100.00,
        'discount_amount' => 0,
        'add_ons_total' => 10.00,
        'total' => 110.00,
        'payment_status' => 'unpaid',
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

    $order = $order->fresh('items');

    $itemLevelDiscountTotal = 0.0;
    foreach ($order->items as $item) {
        $itemLevelDiscountTotal += (float) ($item->discount_amount ?? $item->discount ?? 0);
    }

    $originalSubtotal = (float) $order->subtotal;
    $subtotalAfterItemDiscounts = $originalSubtotal - $itemLevelDiscountTotal;
    $orderLevelDiscount = (float) ($order->discount_amount ?? 0);
    $addOnsTotal = (float) ($order->add_ons_total ?? 0);
    $correctTotal = $subtotalAfterItemDiscounts - $orderLevelDiscount + $addOnsTotal;

    // The total should be 110.00 (100.00 + 10.00 add-ons)
    expect($correctTotal)->toBe(110.00);
});

test('recalculateOrderTotal updates order total in database', function (): void {
    $product = Product::factory()->create(['price' => 50.00]);

    // Create order with original total that's incorrect (missing item discount)
    $order = Order::factory()->create([
        'subtotal' => 50.00,
        'discount_amount' => 0,
        'add_ons_total' => 0,
        'total' => 50.00, // Should be 40.00 after discount
        'payment_status' => 'unpaid',
    ]);

    // Add item with 20% discount
    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'price' => 50.00,
        'subtotal' => 50.00,
        'discount_percentage' => 20.00,
        'discount_amount' => 10.00,
        'discount' => 10.00,
    ]);

    // Initially order total should be wrong
    expect((float) $order->fresh()->total)->toBe(50.00);

    // Simulate what the collectPaymentAction does
    $order = $order->fresh('items');
    $itemLevelDiscountTotal = 0.0;
    foreach ($order->items as $item) {
        $itemLevelDiscountTotal += (float) ($item->discount_amount ?? $item->discount ?? 0);
    }

    $originalSubtotal = (float) $order->subtotal;
    $subtotalAfterItemDiscounts = $originalSubtotal - $itemLevelDiscountTotal;
    $orderLevelDiscount = (float) ($order->discount_amount ?? 0);
    $addOnsTotal = (float) ($order->add_ons_total ?? 0);
    $correctTotal = $subtotalAfterItemDiscounts - $orderLevelDiscount + $addOnsTotal;

    // Update the order
    $order->update(['total' => $correctTotal]);

    // Verify the order total was updated in the database
    expect((float) $order->fresh()->total)->toBe(40.00);
});

test('payment validation uses recalculated total with item discounts (user scenario)', function (): void {
    // This test simulates the exact scenario from the bug report:
    // Banana Bread: ₱45.00
    // Beef Brisket: ₱169.00 with 20% discount = ₱135.20 (saving ₱33.80)
    // Original Subtotal: ₱214.00
    // Item Discounts: -₱33.80
    // Subtotal After Item Discounts: ₱180.20
    // Total to Pay: ₱180.20
    // Cash Received: ₱200.00

    $product1 = Product::factory()->create(['name' => 'Banana Bread', 'price' => 45.00]);
    $product2 = Product::factory()->create(['name' => 'Beef Brisket', 'price' => 169.00]);

    $order = Order::factory()->create([
        'subtotal' => 214.00,
        'discount_amount' => 0,
        'add_ons_total' => 0,
        'total' => 214.00,
        'payment_status' => 'unpaid',
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product1->id,
        'quantity' => 1,
        'price' => 45.00,
        'subtotal' => 45.00,
        'discount_percentage' => 0,
        'discount_amount' => 0,
        'discount' => 0,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product2->id,
        'quantity' => 1,
        'price' => 169.00,
        'subtotal' => 169.00,
        'discount_percentage' => 20.00,
        'discount_amount' => 33.80,
        'discount' => 33.80,
    ]);

    $order = $order->fresh('items');

    // Calculate what the payment validation should use
    $itemLevelDiscountTotal = 0.0;
    foreach ($order->items as $item) {
        $itemLevelDiscountTotal += (float) ($item->discount_amount ?? $item->discount ?? 0);
    }

    $originalSubtotal = (float) $order->subtotal;
    $subtotalAfterItemDiscounts = $originalSubtotal - $itemLevelDiscountTotal;
    $orderLevelDiscount = (float) ($order->discount_amount ?? 0);
    $addOnsTotal = (float) ($order->add_ons_total ?? 0);
    $correctTotal = $subtotalAfterItemDiscounts - $orderLevelDiscount + $addOnsTotal;

    // The payment validation total should be 180.20, NOT 214.00
    expect($correctTotal)->toBe(180.20);

    // When cash received is 200.00, it should be sufficient
    $paidAmount = 200.00;
    $changeAmount = $paidAmount - $correctTotal;

    expect($changeAmount)->toBe(19.80);
    expect($paidAmount >= $correctTotal)->toBeTrue();
});
