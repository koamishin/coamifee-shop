<?php

declare(strict_types=1);

use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Contracts\Cache\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Clear permission cache
    resolve(Factory::class)->clear();

    // Create the super_admin role
    $role = Role::create(['name' => 'super_admin', 'guard_name' => 'web']);

    // Create the permissions for orders
    Permission::create(['name' => 'ViewAny:Order', 'guard_name' => 'web']);
    Permission::create(['name' => 'View:Order', 'guard_name' => 'web']);
    Permission::create(['name' => 'Create:Order', 'guard_name' => 'web']);
    Permission::create(['name' => 'Update:Order', 'guard_name' => 'web']);
    Permission::create(['name' => 'Delete:Order', 'guard_name' => 'web']);

    // Assign all permissions to super_admin role
    $role->givePermissionTo([
        'ViewAny:Order',
        'View:Order',
        'Create:Order',
        'Update:Order',
        'Delete:Order',
    ]);

    $user = User::factory()->create();
    actingAs($user);

    // Grant super_admin role to user
    $user->assignRole('super_admin');

    // Set the current panel for Filament testing
    Filament::setCurrentPanel('admin');
});

it('can load the create order page', function (): void {
    Livewire::test(CreateOrder::class)
        ->assertOk();
});

it('can create an order with default date (current time)', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-01-07 10:30:00'));

    Livewire::test(CreateOrder::class)
        ->assertOk()
        ->fillForm([
            'customer_name' => 'John Doe',
            'order_type' => 'dine-in',
            'payment_method' => 'cash',
            'status' => 'pending',
            'total' => 100.00,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $order = Order::where('customer_name', 'John Doe')->first();

    expect($order)->not->toBeNull();
    expect($order->created_at->format('Y-m-d'))->toBe('2026-01-07');

    Carbon::setTestNow(); // Reset
});

it('can create an order with a backdated order date', function (): void {
    $backdatedDate = Carbon::parse('2025-12-25 14:30:00');

    Livewire::test(CreateOrder::class)
        ->assertOk()
        ->fillForm([
            'customer_name' => 'Jane Smith',
            'order_type' => 'takeaway',
            'payment_method' => 'gcash',
            'status' => 'completed',
            'total' => 250.50,
            'order_date' => $backdatedDate->toDateTimeString(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $order = Order::where('customer_name', 'Jane Smith')->first();

    expect($order)->not->toBeNull();
    expect($order->created_at->format('Y-m-d'))->toBe('2025-12-25');
    expect($order->created_at->format('H:i:s'))->toBe('14:30:00');
    expect($order->updated_at->format('Y-m-d'))->toBe('2025-12-25');
});

it('can create an order backdated to a week ago', function (): void {
    $oneWeekAgo = now()->subWeek();

    Livewire::test(CreateOrder::class)
        ->assertOk()
        ->fillForm([
            'customer_name' => 'Backdated Customer',
            'order_type' => 'delivery',
            'payment_method' => 'grab',
            'status' => 'completed',
            'total' => 500.00,
            'order_date' => $oneWeekAgo->toDateTimeString(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $order = Order::where('customer_name', 'Backdated Customer')->first();

    expect($order)->not->toBeNull();
    expect($order->created_at->format('Y-m-d'))->toBe($oneWeekAgo->format('Y-m-d'));
});

it('saves correct order data when backdating', function (): void {
    $backdatedDate = Carbon::parse('2025-11-15 09:00:00');

    Livewire::test(CreateOrder::class)
        ->assertOk()
        ->fillForm([
            'customer_name' => 'Test Customer',
            'order_type' => 'dine-in',
            'payment_method' => 'cash',
            'status' => 'completed',
            'total' => 75.00,
            'table_number' => 'A5',
            'notes' => 'Test order with backdated date',
            'order_date' => $backdatedDate->toDateTimeString(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas('orders', [
        'customer_name' => 'Test Customer',
        'order_type' => 'dine-in',
        'payment_method' => 'cash',
        'status' => 'completed',
        'table_number' => 'A5',
        'notes' => 'Test order with backdated date',
    ]);

    $order = Order::where('customer_name', 'Test Customer')->first();
    expect($order->created_at->format('Y-m-d H:i:s'))->toBe('2025-11-15 09:00:00');
});

it('displays orders in the list page', function (): void {
    $orders = Order::factory()->count(3)->create();

    Livewire::test(ListOrders::class)
        ->assertOk()
        ->assertCanSeeTableRecords($orders);
});

it('can create an order with products', function (): void {
    // Create test products
    $category = Category::factory()->create();
    $product1 = Product::factory()->create([
        'category_id' => $category->id,
        'price' => 50.00,
        'is_active' => true,
    ]);
    $product2 = Product::factory()->create([
        'category_id' => $category->id,
        'price' => 30.00,
        'is_active' => true,
    ]);

    Livewire::test(CreateOrder::class)
        ->assertOk()
        ->fillForm([
            'customer_name' => 'Product Order Customer',
            'order_type' => 'dine-in',
            'payment_method' => 'cash',
            'status' => 'completed',
            'order_items' => [
                [
                    'product_id' => $product1->id,
                    'quantity' => 2,
                    'price' => 50.00,
                ],
                [
                    'product_id' => $product2->id,
                    'quantity' => 1,
                    'price' => 30.00,
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    // Verify order was created
    $order = Order::where('customer_name', 'Product Order Customer')->first();
    expect($order)->not->toBeNull();
    expect((float) $order->total)->toBe(130.0); // (2 * 50) + (1 * 30)
    expect((float) $order->subtotal)->toBe(130.0);
    expect($order->payment_status)->toBe('paid');

    // Verify order items were created
    expect($order->items)->toHaveCount(2);

    $item1 = OrderItem::where('order_id', $order->id)
        ->where('product_id', $product1->id)
        ->first();
    expect($item1)->not->toBeNull();
    expect($item1->quantity)->toBe(2);
    expect((float) $item1->price)->toBe(50.0);

    $item2 = OrderItem::where('order_id', $order->id)
        ->where('product_id', $product2->id)
        ->first();
    expect($item2)->not->toBeNull();
    expect($item2->quantity)->toBe(1);
    expect((float) $item2->price)->toBe(30.0);
});

it('can create an order with product variants', function (): void {
    // Create product with variants
    $category = Category::factory()->create();
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'price' => 40.00,
        'is_active' => true,
    ]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'name' => 'Large',
        'price' => 55.00,
        'is_active' => true,
    ]);

    Livewire::test(CreateOrder::class)
        ->assertOk()
        ->fillForm([
            'customer_name' => 'Variant Order Customer',
            'order_type' => 'takeaway',
            'payment_method' => 'gcash',
            'status' => 'completed',
            'order_items' => [
                [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => 3,
                    'price' => 55.00,
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    // Verify order was created
    $order = Order::where('customer_name', 'Variant Order Customer')->first();
    expect($order)->not->toBeNull();
    expect((float) $order->total)->toBe(165.0); // 3 * 55

    // Verify order item with variant
    $orderItem = $order->items->first();
    expect($orderItem)->not->toBeNull();
    expect($orderItem->product_variant_id)->toBe($variant->id);
    expect($orderItem->variant_name)->toBe('Large');
    expect($orderItem->quantity)->toBe(3);
    expect((float) $orderItem->price)->toBe(55.0);
});

it('can create a backdated order with products', function (): void {
    $backdatedDate = Carbon::parse('2025-12-01 15:00:00');

    $category = Category::factory()->create();
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'price' => 25.00,
        'is_active' => true,
    ]);

    Livewire::test(CreateOrder::class)
        ->assertOk()
        ->fillForm([
            'customer_name' => 'Backdated Product Customer',
            'order_type' => 'dine-in',
            'payment_method' => 'cash',
            'status' => 'completed',
            'order_date' => $backdatedDate->toDateTimeString(),
            'order_items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 4,
                    'price' => 25.00,
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $order = Order::where('customer_name', 'Backdated Product Customer')->first();
    expect($order)->not->toBeNull();
    expect($order->created_at->format('Y-m-d'))->toBe('2025-12-01');
    expect((float) $order->total)->toBe(100.0); // 4 * 25
    expect($order->items)->toHaveCount(1);
});

it('creates order items with notes', function (): void {
    $category = Category::factory()->create();
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'price' => 20.00,
        'is_active' => true,
    ]);

    Livewire::test(CreateOrder::class)
        ->assertOk()
        ->fillForm([
            'customer_name' => 'Notes Test Customer',
            'order_type' => 'dine-in',
            'payment_method' => 'cash',
            'status' => 'completed',
            'order_items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'price' => 20.00,
                    'notes' => 'Extra hot, no sugar',
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $order = Order::where('customer_name', 'Notes Test Customer')->first();
    expect($order)->not->toBeNull();

    $orderItem = $order->items->first();
    expect($orderItem->notes)->toBe('Extra hot, no sugar');
});
