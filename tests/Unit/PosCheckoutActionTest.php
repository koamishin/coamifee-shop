<?php

declare(strict_types=1);

use App\Actions\PosCheckoutAction;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Ingredient;
use App\Models\IngredientInventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductIngredient;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->action = app(PosCheckoutAction::class);

    // Create category
    $category = Category::factory()->create(['name' => 'Coffee']);

    // Create ingredients
    $coffeeBeans = Ingredient::factory()->create([
        'name' => 'Coffee Beans',
        'is_trackable' => true,
        'unit_type' => 'grams',
    ]);

    $milk = Ingredient::factory()->create([
        'name' => 'Milk',
        'is_trackable' => true,
        'unit_type' => 'ml',
    ]);

    // Create ingredient inventory
    IngredientInventory::factory()->create([
        'ingredient_id' => $coffeeBeans->id,
        'current_stock' => 1000,
        'min_stock_level' => 100,
    ]);

    IngredientInventory::factory()->create([
        'ingredient_id' => $milk->id,
        'current_stock' => 5000,
        'min_stock_level' => 500,
    ]);

    // Create customer
    $this->customer = Customer::factory()->create(['name' => 'John Doe']);

    // Create product
    $this->product = Product::factory()->create([
        'name' => 'Latte',
        'price' => 4.5,
        'category_id' => $category->id,
    ]);

    // Create product ingredients
    ProductIngredient::factory()->create([
        'product_id' => $this->product->id,
        'ingredient_id' => $coffeeBeans->id,
        'quantity_required' => 20,
    ]);

    ProductIngredient::factory()->create([
        'product_id' => $this->product->id,
        'ingredient_id' => $milk->id,
        'quantity_required' => 200,
    ]);
});

test('can checkout successfully', function (): void {
    $cart = [
        $this->product->id => [
            'id' => $this->product->id,
            'name' => $this->product->name,
            'price' => $this->product->price,
            'quantity' => 2,
            'image' => $this->product->image_url,
        ],
    ];

    $orderData = [
        'customer_name' => 'John Doe',
        'order_type' => 'dine-in',
        'payment_method' => 'cash',
        'table_number' => 'A1',
        'total' => 9.0,
        'notes' => 'Extra hot',
    ];

    $result = $this->action->execute($cart, $orderData);

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toBe('Order completed successfully');
    expect($result)->toHaveKey('order_id');
    expect($result)->toHaveKey('order_number');

    // Check order was created
    $order = Order::query()->find($result['order_id']);
    expect($order)->not->toBeNull();
    expect($order->customer_name)->toBe('John Doe');
    expect($order->order_type)->toBe('dine-in');
    expect($order->payment_method)->toBe('cash');
    expect($order->table_number)->toBe('A1');
    expect($order->total)->toBe('9.00');
    expect($order->status)->toBe('completed');
    expect($order->notes)->toBe('Extra hot');

    // Check order items were created
    expect($order->items)->toHaveCount(1);
    $orderItem = $order->items->first();
    expect($orderItem->product_id)->toBe($this->product->id);
    expect($orderItem->quantity)->toBe(2);
    expect($orderItem->price)->toBe('4.50');
});

test('checkout fails with empty cart', function (): void {
    $cart = [];
    $orderData = [
        'customer_name' => 'John Doe',
        'total' => 0,
    ];

    $result = $this->action->execute($cart, $orderData);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toBe('Cart is empty');
});

test('checkout fails with nonexistent product', function (): void {
    $cart = [
        999 => [
            'id' => 999,
            'name' => 'Nonexistent Product',
            'price' => 5.0,
            'quantity' => 1,
        ],
    ];

    $orderData = [
        'customer_name' => 'John Doe',
        'total' => 5.0,
    ];

    $result = $this->action->execute($cart, $orderData);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('not found');
});

test('checkout with insufficient inventory fails', function (): void {
    // Reduce inventory to insufficient levels
    $inventory = IngredientInventory::query()->where('ingredient_id', 1)->first();
    $inventory->update(['current_stock' => 10]); // Less than required 20g

    $cart = [
        $this->product->id => [
            'id' => $this->product->id,
            'name' => $this->product->name,
            'price' => $this->product->price,
            'quantity' => 1,
        ],
    ];

    $orderData = [
        'customer_name' => 'John Doe',
        'total' => 4.5,
    ];

    $result = $this->action->execute($cart, $orderData);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Insufficient ingredients');
});

test('checkout sets guest customer when no name provided', function (): void {
    $cart = [
        $this->product->id => [
            'id' => $this->product->id,
            'name' => $this->product->name,
            'price' => $this->product->price,
            'quantity' => 1,
        ],
    ];

    $orderData = [
        'customer_name' => null,
        'total' => 4.5,
    ];

    $result = $this->action->execute($cart, $orderData);

    expect($result['success'])->toBeTrue();

    $order = Order::query()->find($result['order_id']);
    expect($order->customer_name)->toBe('Guest');
    expect($order->customer_id)->toBeNull();
});

test('checkout links to existing customer', function (): void {
    $cart = [
        $this->product->id => [
            'id' => $this->product->id,
            'name' => $this->product->name,
            'price' => $this->product->price,
            'quantity' => 1,
        ],
    ];

    $orderData = [
        'customer_name' => $this->customer->name,
        'total' => 4.5,
    ];

    $result = $this->action->execute($cart, $orderData);

    expect($result['success'])->toBeTrue();

    $order = Order::query()->find($result['order_id']);
    expect($order->customer_id)->toBe($this->customer->id);
});

test('checkout with default values', function (): void {
    $cart = [
        $this->product->id => [
            'id' => $this->product->id,
            'name' => $this->product->name,
            'price' => $this->product->price,
            'quantity' => 1,
        ],
    ];

    $orderData = ['total' => 4.5]; // Only total is required

    $result = $this->action->execute($cart, $orderData);

    expect($result['success'])->toBeTrue();

    $order = Order::query()->find($result['order_id']);
    expect($order->customer_name)->toBe('Guest');
    expect($order->order_type)->toBe('dine-in');
    expect($order->payment_method)->toBe('cash');
    expect($order->table_number)->toBeNull();
    expect($order->notes)->toBeNull();
});

test('checkout creates multiple order items', function (): void {
    // Create second product
    $secondProduct = Product::factory()->create([
        'name' => 'Cappuccino',
        'price' => 5.0,
    ]);

    $cart = [
        $this->product->id => [
            'id' => $this->product->id,
            'name' => $this->product->name,
            'price' => $this->product->price,
            'quantity' => 2,
        ],
        $secondProduct->id => [
            'id' => $secondProduct->id,
            'name' => $secondProduct->name,
            'price' => $secondProduct->price,
            'quantity' => 1,
        ],
    ];

    $orderData = [
        'customer_name' => 'John Doe',
        'total' => 14.0, // 2*4.50 + 1*5.00
    ];

    $result = $this->action->execute($cart, $orderData);

    expect($result['success'])->toBeTrue();

    $order = Order::query()->find($result['order_id']);
    expect($order->items)->toHaveCount(2);

    $firstItem = $order->items
        ->where('product_id', $this->product->id)
        ->first();
    expect($firstItem->quantity)->toBe(2);

    $secondItem = $order->items
        ->where('product_id', $secondProduct->id)
        ->first();
    expect($secondItem->quantity)->toBe(1);
});

test('calculates order total correctly', function (): void {
    $cart = [
        $this->product->id => [
            'id' => $this->product->id,
            'name' => $this->product->name,
            'price' => $this->product->price,
            'quantity' => 3,
        ],
    ];

    $result = $this->action->calculateOrderTotal($cart, 10.0);

    $expectedSubtotal = 4.5 * 3; // 13.50
    $expectedTax = 13.5 * 0.1; // 1.35
    $expectedTotal = 13.5 + 1.35; // 14.85

    expect($result['subtotal'])->toBe($expectedSubtotal);
    expect($result['tax_amount'])->toBe($expectedTax);
    expect($result['total'])->toBe($expectedTotal);
});

test('handles cart item with notes', function (): void {
    $cart = [
        $this->product->id => [
            'id' => $this->product->id,
            'name' => $this->product->name,
            'price' => $this->product->price,
            'quantity' => 1,
            'notes' => 'Extra hot, no foam',
        ],
    ];

    $orderData = [
        'customer_name' => 'John Doe',
        'total' => 4.5,
    ];

    $result = $this->action->execute($cart, $orderData);

    expect($result['success'])->toBeTrue();

    $order = Order::query()->find($result['order_id']);
    $orderItem = $order->items->first();
    expect($orderItem->notes)->toBe('Extra hot, no foam');
});
