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

beforeEach(function () {
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

it('can place order with pay now and cash payment', function () {
    Livewire::test(PosPage::class)
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
            'customerName' => 'Test Customer',
            'tableNumber' => 'table_1',
            'notes' => 'Test order',
            'paymentTiming' => 'pay_now',
            'paymentMethod' => 'cash',
            'paidAmount' => 250.00,
            'changeAmount' => 50.00,
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    expect(Order::count())->toBe(1);

    $order = Order::first();
    expect($order)
        ->customer_name->toBe('Test Customer')
        ->table_number->toBe('table_1')
        ->payment_status->toBe('paid')
        ->payment_method->toBe('cash')
        ->total->toBe('200.00')
        ->paid_amount->toBe('250.00')
        ->change_amount->toBe('50.00');
});

it('can place order with pay now and GCash payment', function () {
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
            'customerName' => 'GCash Customer',
            'tableNumber' => 'table_2',
            'paymentTiming' => 'pay_now',
            'paymentMethod' => 'gcash',
            'paidAmount' => 100.00,
            'changeAmount' => 0.00,
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    expect(Order::count())->toBe(1);

    $order = Order::first();
    expect($order)
        ->customer_name->toBe('GCash Customer')
        ->payment_status->toBe('paid')
        ->payment_method->toBe('gcash')
        ->paid_amount->toBe('100.00')
        ->change_amount->toBe('0.00');
});

it('can place order with pay now and Maya payment', function () {
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
            'customerName' => 'Maya Customer',
            'tableNumber' => 'table_4',
            'paymentTiming' => 'pay_now',
            'paymentMethod' => 'maya',
            'paidAmount' => 100.00,
            'changeAmount' => 0.00,
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    expect(Order::count())->toBe(1);

    $order = Order::first();
    expect($order)
        ->customer_name->toBe('Maya Customer')
        ->payment_status->toBe('paid')
        ->payment_method->toBe('maya')
        ->paid_amount->toBe('100.00')
        ->change_amount->toBe('0.00');
});

it('can place order with pay later', function () {
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
            'customerName' => 'Pay Later Customer',
            'tableNumber' => 'table_3',
            'paymentTiming' => 'pay_later',
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    expect(Order::count())->toBe(1);

    $order = Order::first();
    expect($order)
        ->customer_name->toBe('Pay Later Customer')
        ->payment_status->toBe('unpaid')
        ->payment_method->toBeNull()
        ->paid_amount->toBeNull()
        ->change_amount->toBeNull();
});

it('can place order with discount and pay now', function () {
    Livewire::test(PosPage::class)
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
            'customerName' => 'Discount Customer',
            'tableNumber' => 'table_4',
            'paymentTiming' => 'pay_now',
            'paymentMethod' => 'cash',
            'discountType' => 'senior_citizen',
            'discountValue' => 20,
            'paidAmount' => 200.00,
            'changeAmount' => 40.00, // 200 - (200 * 0.20) = 160, change = 200 - 160 = 40
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    expect(Order::count())->toBe(1);

    $order = Order::first();
    expect($order)
        ->total->toBe('160.00') // 200 - 40 (20% discount)
        ->discount_amount->toBe('40.00')
        ->paid_amount->toBe('200.00')
        ->change_amount->toBe('40.00');
});
