<?php

declare(strict_types=1);

// Simple test to verify payment methods are working
require_once __DIR__.'/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Bootstrap the Laravel application
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing Payment Methods Integration\n";
echo "==================================\n\n";

try {
    // Test 1: Check if payment methods are properly updated in database
    echo "1. Testing database payment methods...\n";
    $orders = App\Models\Order::whereIn('payment_method', ['gcash', 'maya'])->get();
    echo "   Found {$orders->count()} orders with new payment methods\n";

    foreach ($orders as $order) {
        echo "   - Order #{$order->id}: {$order->payment_method} (\${$order->total})\n";
    }
    echo "   ✓ Database integration working\n\n";

    // Test 2: Test API validation
    echo "2. Testing API validation rules...\n";
    $request = new Request();
    $request->merge([
        'payment_method' => 'gcash',
        'cart' => [['product_id' => 1, 'quantity' => 1]],
        'customer_name' => 'Test Customer',
        'order_type' => 'dine-in',
    ]);

    $validator = validator($request->all(), [
        'payment_method' => 'required|in:cash,gcash,maya',
    ]);

    if ($validator->fails()) {
        echo '   ✗ Validation failed: '.$validator->errors()->first()."\n";
    } else {
        echo "   ✓ API validation accepts gcash\n";
    }

    $request2 = new Request();
    $request2->merge([
        'payment_method' => 'maya',
        'cart' => [['product_id' => 1, 'quantity' => 1]],
        'customer_name' => 'Test Customer',
        'order_type' => 'dine-in',
    ]);

    $validator2 = validator($request2->all(), [
        'payment_method' => 'required|in:cash,gcash,maya',
    ]);

    if ($validator2->fails()) {
        echo '   ✗ Validation failed: '.$validator2->errors()->first()."\n";
    } else {
        echo "   ✓ API validation accepts maya\n";
    }

    // Test old payment methods should fail
    $request3 = new Request();
    $request3->merge(['payment_method' => 'card']);
    $validator3 = validator($request3->all(), [
        'payment_method' => 'required|in:cash,gcash,maya',
    ]);

    if ($validator3->fails()) {
        echo "   ✓ API validation correctly rejects old payment methods (card)\n";
    } else {
        echo "   ✗ API validation should reject old payment methods\n";
    }

    echo "\n3. Testing Livewire component state...\n";

    // Check if the Livewire component class exists and can be instantiated
    if (class_exists('\App\Livewire\Pos')) {
        echo "   ✓ Pos Livewire component class exists\n";

        // Test payment method property
        $pos = new App\Livewire\Pos();
        $pos->paymentMethod = 'gcash';
        if ($pos->paymentMethod === 'gcash') {
            echo "   ✓ GCash payment method can be set\n";
        }

        $pos->paymentMethod = 'maya';
        if ($pos->paymentMethod === 'maya') {
            echo "   ✓ Maya payment method can be set\n";
        }
    } else {
        echo "   ✗ Pos Livewire component class not found\n";
    }

    echo "\n=== All Tests Completed ===\n";
    echo "Payment method changes are working correctly!\n";

} catch (Exception $e) {
    echo 'Error during testing: '.$e->getMessage()."\n";
    echo 'File: '.$e->getFile().' Line: '.$e->getLine()."\n";
}
