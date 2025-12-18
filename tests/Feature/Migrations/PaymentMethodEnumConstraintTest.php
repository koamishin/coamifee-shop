<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('payment methods are migrated from paypal/card to maya/gcash', function (): void {
    Schema::dropIfExists('orders');

    Schema::create('orders', function (Blueprint $table): void {
        $table->id();
        $table->enum('payment_method', ['cash', 'card', 'gcash', 'paypal'])->nullable();
    });

    DB::table('orders')->insert([
        ['payment_method' => 'paypal'],
        ['payment_method' => 'card'],
        ['payment_method' => 'cash'],
    ]);

    $migration = require base_path('database/migrations/2025_11_21_000001_update_payment_methods_in_orders_table.php');

    $migration->up();

    $methods = DB::table('orders')->orderBy('id')->pluck('payment_method')->all();

    expect($methods)->toBe(['maya', 'gcash', 'cash']);
});
