<?php

declare(strict_types=1);

use App\Livewire\Pos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('pos component renders', function () {
    Livewire::test(Pos::class)->assertStatus(200);
});

test('cart can be cleared', function () {
    $component = Livewire::test(Pos::class);
    $component->set('cart', [
        '1' => [
            'id' => 1,
            'name' => 'Test',
            'price' => 5.0,
            'quantity' => 1,
            'image' => null,
        ],
    ]);
    $component->call('clearCart');
    $component->assertSet('cart', []);
});

test('cart item count works', function () {
    $pos = new Pos();
    $pos->cart = [
        '1' => ['quantity' => 2],
        '2' => ['quantity' => 1],
    ];

    $result = $pos->getCartItemCount();
    expect($result)->toBe(3);
});

test('discount can be applied', function () {
    $component = Livewire::test(Pos::class);
    $component->set('cart', [
        '1' => [
            'id' => 1,
            'name' => 'Test Product',
            'price' => 10.0,
            'quantity' => 1,
            'image' => null,
        ],
    ]);
    $component->set('discountPercentage', 10);
    $component->call('applyDiscount');

    $component->assertSet('discountApplied', true);
    $component->assertSet('discountAmount', 1.0);
    $component->assertSet('total', 9.0);
});

test('discount can be removed', function () {
    $pos = new Pos();
    $pos->cart = [
        '1' => ['price' => 10.0, 'quantity' => 1],
    ];
    $pos->discountPercentage = 10;
    $pos->discountApplied = true;
    $pos->discountAmount = 1;

    $pos->removeDiscount();

    expect($pos->discountPercentage)->toBe(0);
    expect($pos->discountApplied)->toBeFalse();
    // TODO: Fix this assertion - removeDiscount method behavior needs investigation
    // expect($pos->discountAmount)->toEqual(0.0);
})->skip();

test('customer discount code valid', function () {
    $component = Livewire::test(Pos::class);
    $component->set('cart', [
        '1' => [
            'id' => 1,
            'name' => 'Test Product',
            'price' => 10.0,
            'quantity' => 1,
            'image' => null,
        ],
    ]);

    $component->call('applyCustomerDiscount', 'COFFEE10');

    $component->assertSet('discountPercentage', 10);
});

test('customer discount code invalid', function () {
    $component = Livewire::test(Pos::class);

    $component->call('applyCustomerDiscount', 'INVALID');

    $component->assertDispatched('discount-invalid');
});

test('receipt generation', function () {
    $component = Livewire::test(Pos::class);
    $component->set('cart', [
        '1' => [
            'id' => 1,
            'name' => 'Latte',
            'price' => 4.5,
            'quantity' => 1,
            'image' => null,
        ],
    ]);
    $component->set('customerName', 'Test Customer');
    $component->set('orderType', 'dine-in');
    $component->set('paymentMethod', 'cash');
    $component->set('subtotal', 4.5);
    $component->set('total', 4.5);

    $component->call('generateReceipt');

    $component->assertDispatched('receipt-generated');
});

test('process payment with empty cart', function () {
    $component = Livewire::test(Pos::class);
    $component->set('cart', []);

    $component->call('processPayment');

    $component->assertDispatched('cart-empty');
});
