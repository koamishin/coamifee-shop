<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\RefundLog;
use App\Models\User;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->refundService = app(RefundService::class);
});

test('refund requires valid admin pin', function () {
    $user = User::factory()->create(['admin_pin' => '1234']);
    $order = Order::factory()->create(['payment_status' => 'paid']);

    $result = $this->refundService->processRefund($order, $user, '9999');

    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toContain('Invalid PIN');
});

test('refund cannot process unpaid orders', function () {
    $user = User::factory()->create(['admin_pin' => '1234']);
    $order = Order::factory()->create(['payment_status' => 'unpaid']);

    $result = $this->refundService->processRefund($order, $user, '1234');

    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toContain('unpaid');
});

test('refund cannot process already refunded orders', function () {
    $user = User::factory()->create(['admin_pin' => '1234']);
    $order = Order::factory()->create(['payment_status' => 'refunded']);

    $result = $this->refundService->processRefund($order, $user, '1234');

    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toContain('already been refunded');
});

test('successful refund updates order status and creates refund log', function () {
    $user = User::factory()->create(['admin_pin' => '5678']);
    $product = Product::factory()->create();
    $order = Order::factory()->create([
        'payment_status' => 'paid',
        'status' => 'pending',
        'payment_method' => 'cash',
        'total' => 100.00,
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'price' => 100.00,
    ]);

    $result = $this->refundService->processRefund($order, $user, '5678');

    expect($result['success'])->toBeTrue()
        ->and($order->refresh()->payment_status)->toBe('refunded')
        ->and($order->refresh()->status)->toBe('refunded')
        ->and(RefundLog::where('order_id', $order->id)->count())->toBe(1);
});

test('refund log contains payment method information', function () {
    $user = User::factory()->create(['admin_pin' => '1111']);
    $product = Product::factory()->create();
    $order = Order::factory()->create([
        'payment_status' => 'paid',
        'status' => 'pending',
        'payment_method' => 'gcash',
        'total' => 150.50,
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'price' => 150.50,
    ]);

    $this->refundService->processRefund($order, $user, '1111');

    $refundLog = RefundLog::where('order_id', $order->id)->first();

    expect($refundLog->payment_method)->toBe('gcash')
        ->and((float) $refundLog->refund_amount)->toBe(150.50)
        ->and($refundLog->refunded_by)->toBe($user->id);
});

test('refund deducts from sales metrics', function () {
    $user = User::factory()->create(['admin_pin' => '2222']);
    $product = Product::factory()->create();

    $order = Order::factory()->create([
        'payment_status' => 'paid',
        'status' => 'pending',
        'payment_method' => 'bank_transfer',
        'total' => 200.00,
        'created_at' => now()->startOfDay(),
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'price' => 100.00,
    ]);

    // Initially order should be counted in metrics
    expect(Order::where('payment_status', '!=', 'refunded')
        ->where('id', $order->id)
        ->count())->toBe(1);

    // Process refund
    $this->refundService->processRefund($order, $user, '2222');

    // After refund, order should be excluded from metrics
    expect(Order::where('payment_status', '!=', 'refunded')
        ->where('id', $order->id)
        ->count())->toBe(0);
});

test('pin verification works correctly', function () {
    $user = User::factory()->create(['admin_pin' => '4444']);

    expect($this->refundService->verifyPin($user, '4444'))->toBeTrue()
        ->and($this->refundService->verifyPin($user, '9999'))->toBeFalse();
});

test('refund logs track all payment methods', function () {
    $user = User::factory()->create(['admin_pin' => '3333']);

    $paymentMethods = ['cash', 'gcash', 'maya', 'bank_transfer'];

    foreach ($paymentMethods as $method) {
        $product = Product::factory()->create();
        $order = Order::factory()->create([
            'payment_status' => 'paid',
            'status' => 'pending',
            'payment_method' => $method,
            'total' => 50.00,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 50.00,
        ]);

        $this->refundService->processRefund($order, $user, '3333');
    }

    $refundLogs = RefundLog::all();

    expect($refundLogs->count())->toBe(4)
        ->and($refundLogs->pluck('payment_method')->unique()->count())->toBe(4);
});
