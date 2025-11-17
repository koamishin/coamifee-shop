<?php

declare(strict_types=1);

use App\Enums\UnitType;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\IngredientInventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductIngredient;
use App\Services\OrderProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a category
    $this->category = Category::factory()->create(['name' => 'Beverages']);

    // Create a product
    $this->product = Product::factory()->create([
        'name' => 'Cappuccino',
        'category_id' => $this->category->id,
        'price' => 120.00,
    ]);

    // Create ingredients
    $this->coffeeBean = Ingredient::factory()->create([
        'name' => 'Coffee Beans',
        'unit_type' => UnitType::GRAMS,
    ]);

    $this->milk = Ingredient::factory()->create([
        'name' => 'Milk',
        'unit_type' => UnitType::MILLILITERS,
    ]);

    // Create inventory for ingredients
    $this->coffeeInventory = IngredientInventory::factory()->create([
        'ingredient_id' => $this->coffeeBean->id,
        'current_stock' => 1000.0, // 1000 grams
        'min_stock_level' => 100.0,
    ]);

    $this->milkInventory = IngredientInventory::factory()->create([
        'ingredient_id' => $this->milk->id,
        'current_stock' => 5000.0, // 5000 ml
        'min_stock_level' => 500.0,
    ]);

    // Link ingredients to product
    ProductIngredient::create([
        'product_id' => $this->product->id,
        'ingredient_id' => $this->coffeeBean->id,
        'quantity_required' => 20.0, // 20g coffee per cappuccino
    ]);

    ProductIngredient::create([
        'product_id' => $this->product->id,
        'ingredient_id' => $this->milk->id,
        'quantity_required' => 150.0, // 150ml milk per cappuccino
    ]);

    $this->orderProcessingService = app(OrderProcessingService::class);
});

test('inventory is deducted when order is processed', function () {
    // Create an order with 2 cappuccinos
    $order = Order::factory()->create([
        'customer_name' => 'Test Customer',
        'status' => 'pending',
        'total' => 240.00,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $this->product->id,
        'quantity' => 2,
        'price' => 120.00,
        'subtotal' => 240.00,
    ]);

    // Get initial stock
    $initialCoffeeStock = $this->coffeeInventory->current_stock;
    $initialMilkStock = $this->milkInventory->current_stock;

    // Process the order
    $result = $this->orderProcessingService->processOrder($order);

    expect($result)->toBeTrue();

    // Refresh inventory
    $this->coffeeInventory->refresh();
    $this->milkInventory->refresh();

    // Check coffee beans: 2 cappuccinos * 20g = 40g should be deducted
    $expectedCoffeeStock = $initialCoffeeStock - 40.0;
    expect((float) $this->coffeeInventory->current_stock)->toBe($expectedCoffeeStock);

    // Check milk: 2 cappuccinos * 150ml = 300ml should be deducted
    $expectedMilkStock = $initialMilkStock - 300.0;
    expect((float) $this->milkInventory->current_stock)->toBe($expectedMilkStock);

    // Check order status
    $order->refresh();
    expect($order->status)->toBe('completed');
    expect($order->inventory_processed)->toBeTrue();
});

test('inventory is not deducted twice for same order', function () {
    // Create an order
    $order = Order::factory()->create([
        'customer_name' => 'Test Customer',
        'status' => 'pending',
        'total' => 120.00,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $this->product->id,
        'quantity' => 1,
        'price' => 120.00,
        'subtotal' => 120.00,
    ]);

    // Get initial stock
    $initialCoffeeStock = $this->coffeeInventory->current_stock;

    // Process the order first time
    $this->orderProcessingService->processOrder($order);

    // Refresh inventory
    $this->coffeeInventory->refresh();
    $stockAfterFirstProcess = $this->coffeeInventory->current_stock;

    // Try to process again
    $order->refresh();
    $this->orderProcessingService->processOrder($order);

    // Refresh inventory again
    $this->coffeeInventory->refresh();
    $stockAfterSecondProcess = $this->coffeeInventory->current_stock;

    // Stock should remain the same after second process
    expect((float) $stockAfterFirstProcess)->toBe((float) $stockAfterSecondProcess);

    // Verify only deducted once
    $expectedStock = $initialCoffeeStock - 20.0; // Only 1 * 20g
    expect((float) $this->coffeeInventory->current_stock)->toBe($expectedStock);
});

test('order fails if insufficient inventory', function () {
    // Set low stock
    $this->coffeeInventory->update(['current_stock' => 10.0]); // Only 10g available

    // Create an order that needs 20g
    $order = Order::factory()->create([
        'customer_name' => 'Test Customer',
        'status' => 'pending',
        'total' => 120.00,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $this->product->id,
        'quantity' => 1,
        'price' => 120.00,
        'subtotal' => 120.00,
    ]);

    // Try to process - should fail
    $result = $this->orderProcessingService->processOrder($order);

    expect($result)->toBeFalse();

    // Order should not be marked as completed
    $order->refresh();
    expect($order->inventory_processed)->toBeFalse();
});

test('ingredient usage is recorded when order is processed', function () {
    // Create an order
    $order = Order::factory()->create([
        'customer_name' => 'Test Customer',
        'status' => 'pending',
        'total' => 120.00,
    ]);

    $orderItem = OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $this->product->id,
        'quantity' => 1,
        'price' => 120.00,
        'subtotal' => 120.00,
    ]);

    // Process the order
    $this->orderProcessingService->processOrder($order);

    // Check ingredient usage was recorded
    $usages = App\Models\IngredientUsage::where('order_item_id', $orderItem->id)->get();

    expect($usages)->toHaveCount(2); // Coffee and milk

    $coffeeUsage = $usages->where('ingredient_id', $this->coffeeBean->id)->first();
    expect($coffeeUsage)->not->toBeNull();
    expect((float) $coffeeUsage->quantity_used)->toBe(20.0);

    $milkUsage = $usages->where('ingredient_id', $this->milk->id)->first();
    expect($milkUsage)->not->toBeNull();
    expect((float) $milkUsage->quantity_used)->toBe(150.0);
});

test('product metrics are recorded when order is processed', function () {
    // Create an order
    $order = Order::factory()->create([
        'customer_name' => 'Test Customer',
        'status' => 'pending',
        'total' => 240.00,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $this->product->id,
        'quantity' => 2,
        'price' => 120.00,
        'subtotal' => 240.00,
    ]);

    // Process the order
    $this->orderProcessingService->processOrder($order);

    // Check product metrics were recorded
    $metrics = App\Models\ProductMetric::where('product_id', $this->product->id)->get();

    expect($metrics)->not->toBeEmpty();
});

test('can fulfill order checks inventory correctly', function () {
    // Create an order
    $order = Order::factory()->create([
        'customer_name' => 'Test Customer',
        'status' => 'pending',
        'total' => 120.00,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $this->product->id,
        'quantity' => 1,
        'price' => 120.00,
        'subtotal' => 120.00,
    ]);

    // Should be able to fulfill with current stock
    $canFulfill = $this->orderProcessingService->canFulfillOrder($order);
    expect($canFulfill)->toBeTrue();

    // Set insufficient stock
    $this->coffeeInventory->update(['current_stock' => 10.0]);

    // Refresh order to clear any caching
    $order->refresh();

    // Should not be able to fulfill now
    $canFulfill = $this->orderProcessingService->canFulfillOrder($order);
    expect($canFulfill)->toBeFalse();
});

test('order with variant deducts inventory correctly', function () {
    // Create a beverage with variant
    $beverage = Product::factory()->create([
        'name' => 'Coffee',
        'category_id' => 1, // Beverages
        'price' => 89.00,
    ]);

    $hotVariant = App\Models\ProductVariant::create([
        'product_id' => $beverage->id,
        'name' => 'Hot',
        'price' => 89.00,
        'is_default' => true,
        'is_active' => true,
        'sort_order' => 0,
    ]);

    // Link ingredient to product
    ProductIngredient::create([
        'product_id' => $beverage->id,
        'ingredient_id' => $this->coffeeBean->id,
        'quantity_required' => 15.0,
    ]);

    // Create order with variant
    $order = Order::factory()->create([
        'customer_name' => 'Test Customer',
        'status' => 'pending',
        'total' => 89.00,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $beverage->id,
        'product_variant_id' => $hotVariant->id,
        'variant_name' => 'Hot',
        'quantity' => 1,
        'price' => 89.00,
        'subtotal' => 89.00,
    ]);

    $initialStock = $this->coffeeInventory->current_stock;

    // Process order
    $result = $this->orderProcessingService->processOrder($order);

    expect($result)->toBeTrue();

    // Check inventory was deducted
    $this->coffeeInventory->refresh();
    $expectedStock = $initialStock - 15.0;
    expect((float) $this->coffeeInventory->current_stock)->toBe($expectedStock);
});
