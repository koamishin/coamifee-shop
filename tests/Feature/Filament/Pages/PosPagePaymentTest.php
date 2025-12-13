<?php

declare(strict_types=1);

use App\Filament\Pages\PosPage;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    actingAs($this->user);

    // Create a category and product for testing
    $this->category = Category::factory()->create(['is_active' => true]);
    $this->product = Product::factory()->create([
        'category_id' => $this->category->id,
        'price' => 100.00,
        'is_active' => true,
    ]);
});

it('can place order with pay now and cash payment', function (): void {
    Livewire::test(PosPage::class)
        ->set('isTabletMode', true)
        ->set('paidAmount', 250.00)
        ->set('orderType', 'dine_in')
        ->set('cartItems', [
            [
                'product_id' => $this->product->id,
                'variant_id' => null,
                'variant_name' => null,
                'name' => $this->product->name,
                'price' => 100.00,
                'quantity' => 2,
                'subtotal' => 200.00,
            ],
        ])
        ->set('totalAmount', 200.00)
        ->mountAction('placeOrder')
        ->fillForm([
            'tableNumber' => 'table_1',
            'paymentTiming' => 'pay_now',
            'paymentMethod' => 'cash',
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    expect(Order::query()->count())->toBe(1);

    $order = Order::query()->first();
    expect($order)
        ->customer_name->toBe('Walk-in Customer')
        ->table_number->toBe('table_1')
        ->payment_status->toBe('paid')
        ->payment_method->toBe('cash')
        ->total->toBe('200.00')
        ->paid_amount->toBe('250.00')
        ->change_amount->toBe('50.00');
});

it('can place order with pay now and GCash payment', function (): void {
    Livewire::test(PosPage::class)
        ->set('orderType', 'dine_in')
        ->set('cartItems', [
            [
                'product_id' => $this->product->id,
                'variant_id' => null,
                'variant_name' => null,
                'name' => $this->product->name,
                'price' => 100.00,
                'quantity' => 1,
                'subtotal' => 100.00,
            ],
        ])
        ->set('totalAmount', 100.00)
        ->mountAction('placeOrder')
        ->fillForm([
            'tableNumber' => 'table_2',
            'paymentTiming' => 'pay_now',
            'paymentMethod' => 'gcash',
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    expect(Order::query()->count())->toBe(1);

    $order = Order::query()->first();
    expect($order)
        ->customer_name->toBe('Walk-in Customer')
        ->payment_status->toBe('paid')
        ->payment_method->toBe('gcash')
        ->paid_amount->toBe('100.00')
        ->change_amount->toBe('0.00');
});

it('can place order with pay now and Maya payment', function (): void {
    Livewire::test(PosPage::class)
        ->set('orderType', 'dine_in')
        ->set('cartItems', [
            [
                'product_id' => $this->product->id,
                'variant_id' => null,
                'variant_name' => null,
                'name' => $this->product->name,
                'price' => 100.00,
                'quantity' => 1,
                'subtotal' => 100.00,
            ],
        ])
        ->set('totalAmount', 100.00)
        ->mountAction('placeOrder')
        ->fillForm([
            'tableNumber' => 'table_4',
            'paymentTiming' => 'pay_now',
            'paymentMethod' => 'maya',
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    expect(Order::query()->count())->toBe(1);

    $order = Order::query()->first();
    expect($order)
        ->customer_name->toBe('Walk-in Customer')
        ->payment_status->toBe('paid')
        ->payment_method->toBe('maya')
        ->paid_amount->toBe('100.00')
        ->change_amount->toBe('0.00');
});

it('can place order with pay later', function (): void {
    Livewire::test(PosPage::class)
        ->set('orderType', 'dine_in')
        ->set('cartItems', [
            [
                'product_id' => $this->product->id,
                'variant_id' => null,
                'variant_name' => null,
                'name' => $this->product->name,
                'price' => 100.00,
                'quantity' => 1,
                'subtotal' => 100.00,
            ],
        ])
        ->set('totalAmount', 100.00)
        ->mountAction('placeOrder')
        ->fillForm([
            'tableNumber' => 'table_3',
            'orderType' => 'dine_in',
            'paymentTiming' => 'pay_later',
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    expect(Order::query()->count())->toBe(1);

    $order = Order::query()->first();
    expect($order)
        ->customer_name->toBe('Walk-in Customer')
        ->payment_status->toBe('unpaid')
        ->payment_method->toBeNull()
        ->paid_amount->toBeNull()
        ->change_amount->toBeNull();
});

it('can place order with discount and pay now', function (): void {
    Livewire::test(PosPage::class)
        ->set('isTabletMode', true)
        ->set('paidAmount', 200.00)
        ->set('orderType', 'dine_in')
        ->set('cartItems', [
            [
                'product_id' => $this->product->id,
                'variant_id' => null,
                'variant_name' => null,
                'name' => $this->product->name,
                'price' => 100.00,
                'quantity' => 2,
                'subtotal' => 200.00,
            ],
        ])
        ->set('totalAmount', 200.00)
        ->mountAction('placeOrder')
        ->fillForm([
            'tableNumber' => 'table_4',
            'paymentTiming' => 'pay_now',
            'paymentMethod' => 'cash',
            'discountType' => 'senior',
            'discountValue' => 20,
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    expect(Order::query()->count())->toBe(1);

    $order = Order::query()->first();
    expect($order)
        ->total->toBe('160.00') // 200 - 40 (20% discount)
        ->discount_amount->toBe('40.00')
        ->paid_amount->toBe('200.00')
        ->change_amount->toBe('40.00');
});

it('can place order with delivery and Grab payment', function (): void {
    Livewire::test(PosPage::class)
        ->set('orderType', 'delivery')
        ->set('cartItems', [
            [
                'product_id' => $this->product->id,
                'variant_id' => null,
                'variant_name' => null,
                'name' => $this->product->name,
                'price' => 100.00,
                'quantity' => 1,
                'subtotal' => 100.00,
            ],
        ])
        ->set('totalAmount', 100.00)
        ->mountAction('placeOrder')
        ->fillForm([
            'orderType' => 'delivery',
            'paymentTiming' => 'pay_now',
            'paymentMethod' => 'grab',
            'notes' => 'Deliver to Main Street',
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    expect(Order::query()->count())->toBe(1);

    $order = Order::query()->first();
    expect($order)
        ->order_type->toBe('delivery')
        ->payment_status->toBe('paid')
        ->payment_method->toBe('grab')
        ->total->toBe('100.00')
        ->paid_amount->toBe('100.00')
        ->change_amount->toBe('0.00');
});

it('can place order with delivery and Food Panda payment', function (): void {
    Livewire::test(PosPage::class)
        ->set('orderType', 'delivery')
        ->set('cartItems', [
            [
                'product_id' => $this->product->id,
                'variant_id' => null,
                'variant_name' => null,
                'name' => $this->product->name,
                'price' => 100.00,
                'quantity' => 1,
                'subtotal' => 100.00,
            ],
        ])
        ->set('totalAmount', 100.00)
        ->mountAction('placeOrder')
        ->fillForm([
            'orderType' => 'delivery',
            'deliveryProvider' => 'food_panda',
            'paymentTiming' => 'pay_now',
            'notes' => 'Deliver to Oak Avenue',
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    expect(Order::query()->count())->toBe(1);

    $order = Order::query()->first();
    expect($order)
        ->order_type->toBe('delivery')
        ->payment_status->toBe('paid')
        ->payment_method->toBe('food_panda')
        ->total->toBe('100.00')
        ->paid_amount->toBe('100.00')
        ->change_amount->toBe('0.00');
});

it('correctly calculates change with paid amount 50 and total 45', function (): void {
    Livewire::test(PosPage::class)
        ->set('isTabletMode', true)
        ->set('paidAmount', 50.00)
        ->set('orderType', 'dine_in')
        ->set('cartItems', [
            [
                'product_id' => $this->product->id,
                'variant_id' => null,
                'variant_name' => null,
                'name' => $this->product->name,
                'price' => 45.00,
                'quantity' => 1,
                'subtotal' => 45.00,
            ],
        ])
        ->set('totalAmount', 45.00)
        ->mountAction('placeOrder')
        ->fillForm([
            'tableNumber' => 'table_1',
            'paymentTiming' => 'pay_now',
            'paymentMethod' => 'cash',
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    expect(Order::query()->count())->toBe(1);

    $order = Order::query()->first();
    expect($order)
        ->total->toBe('45.00')
        ->paid_amount->toBe('50.00')
        ->change_amount->toBe('5.00');
});
