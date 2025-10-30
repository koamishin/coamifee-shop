<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Customer;
use App\Models\Ingredient;
use App\Models\IngredientInventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductIngredient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Create categories
    $coffeeCategory = Category::factory()->create(['name' => 'Coffee']);
    $pastryCategory = Category::factory()->create(['name' => 'Pastry']);

    // Create ingredients
    $coffeeBeans = Ingredient::factory()->create([
        'name' => 'Coffee Beans',
        'unit_type' => 'grams',
    ]);

    $milk = Ingredient::factory()->create([
        'name' => 'Milk',
        'unit_type' => 'ml',
    ]);

    $flour = Ingredient::factory()->create([
        'name' => 'Flour',
        'unit_type' => 'grams',
    ]);

    // Create ingredient inventory
    IngredientInventory::factory()->create([
        'ingredient_id' => $coffeeBeans->id,
        'current_stock' => 1000,
        'min_stock_level' => 100,
    ]);

    IngredientInventory::factory()->create([
        'ingredient_id' => $milk->id,
        'current_stock' => 10000,
        'min_stock_level' => 1000,
    ]);

    // Create products
    $this->latte = Product::factory()->create([
        'name' => 'Latte',
        'price' => 4.5,
        'category_id' => $coffeeCategory->id,
    ]);

    $this->cappuccino = Product::factory()->create([
        'name' => 'Cappuccino',
        'price' => 4.75,
        'category_id' => $coffeeCategory->id,
    ]);

    // Create product ingredients
    ProductIngredient::factory()->create([
        'product_id' => $this->latte->id,
        'ingredient_id' => $coffeeBeans->id,
        'quantity_required' => 18,
    ]);

    ProductIngredient::factory()->create([
        'product_id' => $this->latte->id,
        'ingredient_id' => $milk->id,
        'quantity_required' => 250,
    ]);

    ProductIngredient::factory()->create([
        'product_id' => $this->cappuccino->id,
        'ingredient_id' => $coffeeBeans->id,
        'quantity_required' => 20,
    ]);

    ProductIngredient::factory()->create([
        'product_id' => $this->cappuccino->id,
        'ingredient_id' => $milk->id,
        'quantity_required' => 150,
    ]);

    $this->actingAs(User::factory()->create());
});

test('complete pos workflow', function (): void {
    // Add products to cart using Livewire test
    $pos = Livewire::test('pos');

    // Add products to cart
    $pos->call('addToCart', $this->latte->id)
        ->call('addToCart', $this->cappuccino->id)
        ->set('total', 13.0)
        ->call('confirmPayment');

    // Verify order was created
    expect(Order::query()->where('customer_name', 'Guest')->first())
        ->not->toBeNull()
        ->status->toBe('completed');
});

test('prevents order with insufficient inventory', function (): void {
    // Reduce inventory to insufficient levels
    $inventory = IngredientInventory::query()->where('ingredient_id', 1)->first();
    $inventory->update(['current_stock' => 10]); // Less than 18g needed for latte

    Livewire::test('sidebar')
        ->call('addToCart', $this->latte->id)
        ->assertDispatched('insufficient-inventory')
        ->assertNotDispatched('productSelected');

    // Verify order cannot be completed
    Livewire::test('pos')
        ->set('cart', [
            $this->latte->id => [
                'id' => $this->latte->id,
                'name' => 'Latte',
                'price' => 4.5,
                'quantity' => 1,
            ],
        ])
        ->set('total', 4.5)
        ->call('confirmPayment')
        ->assertDispatched('order-failed');

    // Verify no order was created
    expect(Order::query()->where('customer_name', 'John Doe')->first())->toBeNull();
});

test('handles customizations and variants', function (): void {
    Livewire::test('pos')
        ->call('quickAddProduct', $this->latte->id, 'large', 'iced')
        ->assertSet('cart.'.$this->latte->id.'_large_iced.size', 'large')
        ->assertSet(
            'cart.'.$this->latte->id.'_large_iced.temperature',
            'iced',
        )
        ->assertSet('cart.'.$this->latte->id.'_large_iced.price', 5.625); // 4.50 * 1.25

    Livewire::test('pos')
        ->set('cart', [
            $this->latte->id.'_large_iced' => [
                'id' => $this->latte->id,
                'name' => 'Latte',
                'price' => 5.625,
                'quantity' => 1,
                'size' => 'large',
                'temperature' => 'iced',
            ],
        ])
        ->call('customizeCartItem', $this->latte->id.'_large_iced', [
            'milk' => 'oat',
            'extra_shots' => 1,
        ])
        ->assertSet('cart.'.$this->latte->id.'_large_iced.customizations', [
            'milk' => 'oat',
            'extra_shots' => 1,
        ]);
});

test('applies discounts correctly', function (): void {
    Livewire::test('pos')
        ->set('cart', [
            $this->latte->id => [
                'id' => $this->latte->id,
                'name' => 'Latte',
                'price' => 4.5,
                'quantity' => 2,
            ],
        ])
        ->call('applyCustomerDiscount', 'COFFEE10')
        ->assertDispatched('discount-applied')
        ->assertSet('discountPercentage', 10)
        ->assertSet('discountAmount', 0.9) // 10% of 9.00
        ->assertSet('total', 8.1);
});

test('rejects invalid discount code', function (): void {
    Livewire::test('pos')
        ->call('applyCustomerDiscount', 'INVALID')
        ->assertDispatched('discount-invalid');
});

test('can duplicate previous order', function (): void {
    // Create a previous order
    $previousOrder = Order::factory()->create([
        'customer_name' => 'Jane Smith',
        'order_type' => 'dine-in',
        'payment_method' => 'cash',
        'table_number' => 'A3',
        'total' => 14.0,
        'status' => 'completed',
    ]);

    OrderItem::factory()->create([
        'order_id' => $previousOrder->id,
        'product_id' => $this->latte->id,
        'quantity' => 2,
        'price' => 4.5,
    ]);

    OrderItem::factory()->create([
        'order_id' => $previousOrder->id,
        'product_id' => $this->cappuccino->id,
        'quantity' => 1,
        'price' => 5.0,
    ]);

    // Duplicate the order
    Livewire::test('pos')
        ->call('duplicateOrder', $previousOrder->id)
        ->assertDispatched('order-duplicated')
        ->assertSet('customerName', 'Jane Smith')
        ->assertSet('orderType', 'dine-in')
        ->assertSet('tableNumber', 'A3')
        ->assertSet('cart.'.$this->latte->id.'.quantity', 2)
        ->assertSet('cart.'.$this->cappuccino->id.'.quantity', 1);

    // Calculate expected total: 2*4.50 + 1*5.00 = 14.00
    $pos = Livewire::test('pos')->set('cart', [
        $this->latte->id => [
            'id' => $this->latte->id,
            'name' => 'Latte',
            'price' => 4.5,
            'quantity' => 2,
        ],
        $this->cappuccino->id => [
            'id' => $this->cappuccino->id,
            'name' => 'Cappuccino',
            'price' => 5.0,
            'quantity' => 1,
        ],
    ]);

    // After adding items, totals should be calculated automatically
    expect($pos->get('subtotal'))->not->toBeNull();
    expect($pos->get('total'))->not->toBeNull();
});

test('filters products by category', function (): void {
    Livewire::test('sidebar')
        ->set('selectedCategory', 1) // Assuming category 1 is Coffee
        ->assertViewHas('products', fn ($products) => $products->every('category.name', 'Coffee'));
});

test('generates receipt with all details', function (): void {
    Livewire::test('pos')
        ->set('cart', [
            $this->latte->id => [
                'id' => $this->latte->id,
                'name' => 'Latte',
                'price' => 4.5,
                'quantity' => 1,
                'customizations' => ['milk' => 'oat'],
            ],
        ])
        ->set('customerName', 'John Doe')
        ->set('orderType', 'takeout')
        ->set('paymentMethod', 'card')
        ->set('subtotal', 5.0)
        ->set('total', 5.0)
        ->call('generateReceipt')
        ->assertDispatched('receipt-generated');
});

test('searches products correctly', function (): void {
    Livewire::test('pos')
        ->set('search', 'Latte')
        ->assertViewHas('products', fn ($products) => $products->contains('name', 'Latte'));
});

test('loads recent orders', function (): void {
    // Create some recent orders
    Order::factory()
        ->count(5)
        ->create(['created_at' => now()->subDays(1)]);

    Livewire::test('pos')
        ->assertViewHas('recentOrders')
        ->assertSet('recentOrders', fn ($orders): bool => $orders->count() <= 5);
});

test('displays today statistics', function (): void {
    // Create some orders for today
    Order::factory()->create(['total' => 15.0, 'created_at' => now()]);
    Order::factory()->create(['total' => 25.0, 'created_at' => now()]);
    Order::factory()->create([
        'total' => 10.0,
        'created_at' => now()->subDay(),
    ]);

    Livewire::test('pos')
        ->assertSet('todayOrders', 2)
        ->assertSet('todaySales', 40.0); // 15.00 + 25.00
});

test('handles multiple customers', function (): void {
    // Create multiple customers
    Customer::factory()->create(['name' => 'John Doe']);
    Customer::factory()->create(['name' => 'Jane Smith']);

    Livewire::test('pos')
        ->set('customerSearch', 'John')
        ->assertViewHas('customers', fn ($customers) => $customers->contains('name', 'John Doe'));
});
