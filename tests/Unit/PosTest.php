<?php

declare(strict_types=1);

use App\Livewire\Pos;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\IngredientInventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductIngredient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('pos component renders successfully', function (): void {
    Livewire::test(Pos::class)->assertStatus(200)->assertViewIs('livewire.pos');
});

test('can add product to cart', function (): void {
    Livewire::test(Pos::class)
        ->call('addToCart', $this->product->id)
        ->assertSet('cart.'.$this->product->id.'.quantity', 1)
        ->assertSet(
            'cart.'.$this->product->id.'.name',
            $this->product->name,
        )
        ->assertSet(
            'cart.'.$this->product->id.'.price',
            $this->product->price,
        );
});

test('cannot add product with insufficient inventory', function (): void {
    // Get the coffee beans ingredient (used in the product)
    $coffeeIngredient = Ingredient::where('name', 'Coffee Beans')->first();
    $inventory = IngredientInventory::where('ingredient_id', $coffeeIngredient->id)->first();
    $inventory->update(['current_stock' => 10]); // Less than required 20g

    Livewire::test(Pos::class)
        ->call('addToCart', $this->product->id)
        ->assertDispatched('insufficient-inventory')
        ->assertSet('cart', []);
});

test('can increment cart item quantity', function (): void {
    Livewire::test(Pos::class)
        ->call('addToCart', $this->product->id)
        ->call('incrementQuantity', $this->product->id)
        ->assertSet('cart.'.$this->product->id.'.quantity', 2);
});

test('cannot increment beyond available inventory', function (): void {
    // Get both ingredients used in the product
    $coffeeIngredient = Ingredient::where('name', 'Coffee Beans')->first();
    $milkIngredient = Ingredient::where('name', 'Milk')->first();

    // Set inventory to only allow 1 unit (20g coffee + 200ml milk)
    IngredientInventory::where('ingredient_id', $coffeeIngredient->id)->first()->update(['current_stock' => 20]);
    IngredientInventory::where('ingredient_id', $milkIngredient->id)->first()->update(['current_stock' => 200]);

    Livewire::test(Pos::class)
        ->call('addToCart', $this->product->id)
        ->call('incrementQuantity', $this->product->id)
        ->assertDispatched('insufficient-inventory')
        ->assertSet('cart.'.$this->product->id.'.quantity', 1);
});

test('can decrement cart item quantity', function (): void {
    Livewire::test(Pos::class)
        ->call('addToCart', $this->product->id)
        ->call('incrementQuantity', $this->product->id)
        ->call('decrementQuantity', $this->product->id)
        ->assertSet('cart.'.$this->product->id.'.quantity', 1);
});

test('decrement to zero removes item', function (): void {
    Livewire::test(Pos::class)
        ->call('addToCart', $this->product->id)
        ->call('decrementQuantity', $this->product->id)
        ->assertSet('cart.'.$this->product->id, null);
});

test('can remove item from cart', function (): void {
    Livewire::test(Pos::class)
        ->call('addToCart', $this->product->id)
        ->call('removeFromCart', $this->product->id)
        ->assertSet('cart.'.$this->product->id, null);
});

test('can clear cart', function (): void {
    Livewire::test(Pos::class)
        ->call('addToCart', $this->product->id)
        ->call('clearCart')
        ->assertSet('cart', []);
});

test('calculates totals correctly', function (): void {
    Livewire::test(Pos::class)
        ->call('addToCart', $this->product->id)
        ->call('incrementQuantity', $this->product->id)
        ->assertSet('subtotal', 9.0) // 2 * 4.50
        ->assertSet('total', 9.0);
});

test('can apply discount', function (): void {
    Livewire::test(Pos::class)
        ->call('addToCart', $this->product->id)
        ->set('discountPercentage', 10)
        ->call('applyDiscount')
        ->assertSet('discountApplied', true)
        ->assertSet('discountAmount', 0.45) // 10% of 4.50
        ->assertSet('total', 4.05);
});

test('can quick add product with size', function (): void {
    Livewire::test(Pos::class)
        ->call('quickAddProduct', $this->product->id, 'large', 'hot')
        ->assertSet('cart.'.$this->product->id.'_large_hot.size', 'large')
        ->assertSet(
            'cart.'.$this->product->id.'_large_hot.temperature',
            'hot',
        )
        ->assertSet('cart.'.$this->product->id.'_large_hot.price', 5.625); // 4.50 * 1.25
});

test('can apply customer discount code', function (): void {
    Livewire::test(Pos::class)
        ->call('addToCart', $this->product->id)
        ->call('applyCustomerDiscount', 'COFFEE10')
        ->assertDispatched('discount-applied')
        ->assertSet('discountPercentage', 10)
        ->assertSet('total', 4.05);
});

test('invalid discount code is rejected', function (): void {
    Livewire::test(Pos::class)
        ->call('addToCart', $this->product->id)
        ->call('applyCustomerDiscount', 'INVALID')
        ->assertDispatched('discount-invalid');
});

test('can duplicate existing order', function (): void {
    // Create a completed order
    $order = Order::factory()->create([
        'customer_name' => 'John Doe',
        'total' => 9.0,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $this->product->id,
        'quantity' => 2,
        'price' => 4.5,
    ]);

    Livewire::test(Pos::class)
        ->call('duplicateOrder', $order->id)
        ->assertDispatched('order-duplicated')
        ->assertSet('customerName', 'John Doe')
        ->assertSet('cart.'.$this->product->id.'.quantity', 2);
});

test('cannot duplicate order with insufficient inventory', function (): void {
    // Create order with 3 units
    $order = Order::factory()->create();
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $this->product->id,
        'quantity' => 3,
    ]);

    // Get both ingredients used in the product
    $coffeeIngredient = Ingredient::where('name', 'Coffee Beans')->first();
    $milkIngredient = Ingredient::where('name', 'Milk')->first();

    // Set inventory to be insufficient for 3 units
    // 3 units need: 3 * 20g = 60g coffee, 3 * 200ml = 600ml milk
    // We'll set inventory to only allow 2 units: 2 * 20g = 40g coffee, 2 * 200ml = 400ml milk
    IngredientInventory::where('ingredient_id', $coffeeIngredient->id)->first()->update(['current_stock' => 40]);
    IngredientInventory::where('ingredient_id', $milkIngredient->id)->first()->update(['current_stock' => 400]);

    Livewire::test(Pos::class)
        ->call('duplicateOrder', $order->id)
        ->assertDispatched('insufficient-inventory');
});

test('can generate receipt', function (): void {
    Livewire::test(Pos::class)
        ->call('addToCart', $this->product->id)
        ->set('customerName', 'Test Customer')
        ->call('generateReceipt')
        ->assertDispatched('receipt-generated');
});

test('gets cart item count', function (): void {
    $result = Livewire::test(Pos::class)
        ->call('addToCart', $this->product->id)
        ->call('incrementQuantity', $this->product->id)
        ->call('addToCart', $this->product->id); // Same product again

    $cart = $result->get('cart');
    // Since we added twice and incremented once, should be 3
    expect($cart[$this->product->id]['quantity'])->toBe(3);
});

test('process payment with empty cart fails', function (): void {
    Livewire::test(Pos::class)
        ->call('processPayment')
        ->assertDispatched('cart-empty');
});

test('can customize cart item', function (): void {
    Livewire::test(Pos::class)
        ->call('addToCart', $this->product->id)
        ->call('customizeCartItem', $this->product->id, [
            'milk' => 'oat',
            'extra_shots' => 1,
        ])
        ->assertSet('cart.'.$this->product->id.'.customizations', [
            'milk' => 'oat',
            'extra_shots' => 1,
        ]);
});

// Helper function to create test data
beforeEach(function (): void {
    // Create category
    $category = Category::factory()->create(['name' => 'Coffee']);

    // Create ingredients
    $coffeeBeans = Ingredient::factory()->create([
        'name' => 'Coffee Beans',
        'unit_type' => 'grams',
    ]);

    $milk = Ingredient::factory()->create([
        'name' => 'Milk',
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
