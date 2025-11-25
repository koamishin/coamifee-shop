<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\OrderCancellation;
use App\Models\User;
use App\Services\OrderCancellationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can cancel unpaid order in progress', function () {
    $user = User::factory()->create(['admin_pin' => '1234']);
    $order = Order::factory()->create([
        'status' => 'pending',
        'payment_status' => 'unpaid',
        'total' => 100.00,
    ]);

    $service = app(OrderCancellationService::class);
    $result = $service->processCancellation($order, $user, '1234');

    expect($result['success'])->toBeTrue($result['message'] ?? 'No message provided');
    expect($result['message'])->toContain('Order #'.$order->id);
    expect($order->refresh()->status)->toBe('cancelled');
    expect($order->payment_status)->toBe('cancelled');
});

test('cannot cancel order without correct PIN', function () {
    $user = User::factory()->create(['admin_pin' => '1234']);
    $order = Order::factory()->create([
        'status' => 'pending',
        'payment_status' => 'unpaid',
    ]);

    $service = app(OrderCancellationService::class);
    $result = $service->processCancellation($order, $user, 'wrong');

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Invalid PIN');
});

test('cannot cancel order that is not pending and unpaid', function () {
    $user = User::factory()->create(['admin_pin' => '1234']);
    $order = Order::factory()->create([
        'status' => 'completed',
        'payment_status' => 'paid',
    ]);

    $service = app(OrderCancellationService::class);
    $result = $service->processCancellation($order, $user, '1234');

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('cannot be cancelled');
});

test('cancellation creates history record', function () {
    $user = User::factory()->create(['admin_pin' => '1234']);
    $order = Order::factory()->create([
        'status' => 'pending',
        'payment_status' => 'unpaid',
        'total' => 150.00,
    ]);

    $service = app(OrderCancellationService::class);
    $reason = 'Customer requested cancellation';
    $service->processCancellation($order, $user, '1234', $reason);

    $cancellation = OrderCancellation::where('order_id', $order->id)->first();
    expect($cancellation)->not->toBeNull();
    expect($cancellation->cancelled_by)->toBe($user->id);
    expect($cancellation->cancellation_amount)->toBe('150.00');
    expect($cancellation->reason)->toBe($reason);
});

test('can check if order can be cancelled', function () {
    $cancelableOrder = Order::factory()->create([
        'status' => 'pending',
        'payment_status' => 'unpaid',
    ]);

    $notCancelableOrder = Order::factory()->create([
        'status' => 'completed',
        'payment_status' => 'paid',
    ]);

    $service = app(OrderCancellationService::class);

    expect($service->canCancelOrder($cancelableOrder))->toBeTrue();
    expect($service->canCancelOrder($notCancelableOrder))->toBeFalse();
});
