<?php

declare(strict_types=1);

use App\Filament\Pages\BestSellers;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    actingAs($this->user);
});

it('displays the best sellers page', function () {
    Livewire::test(BestSellers::class)
        ->assertSuccessful()
        ->assertSee('No sales data available');
});

it('shows completed order products in best sellers', function () {
    $category = Category::factory()->create(['name' => 'Test Category']);
    $product = Product::factory()->create([
        'name' => 'Test Product',
        'category_id' => $category->id,
        'price' => 100,
    ]);

    $order = Order::factory()->create([
        'status' => 'completed',
        'created_at' => now()->subDays(5),
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 5,
        'price' => 100,
    ]);

    Livewire::test(BestSellers::class)
        ->assertSuccessful()
        ->assertSee('Test Category')
        ->assertSee('Test Product')
        ->assertSee('5')
        ->assertSee('500');
});

it('does not show pending order products in best sellers', function () {
    $category = Category::factory()->create(['name' => 'Pending Category']);
    $product = Product::factory()->create([
        'name' => 'Pending Product',
        'category_id' => $category->id,
        'price' => 100,
    ]);

    $order = Order::factory()->create([
        'status' => 'pending',
        'created_at' => now()->subDays(5),
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 5,
        'price' => 100,
    ]);

    Livewire::test(BestSellers::class)
        ->assertSuccessful()
        ->assertDontSee('Pending Product');
});

it('does not show orders older than 30 days', function () {
    $category = Category::factory()->create(['name' => 'Old Category']);
    $product = Product::factory()->create([
        'name' => 'Old Product',
        'category_id' => $category->id,
        'price' => 100,
    ]);

    $order = Order::factory()->create([
        'status' => 'completed',
        'created_at' => now()->subDays(35),
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 5,
        'price' => 100,
    ]);

    Livewire::test(BestSellers::class)
        ->assertSuccessful()
        ->assertDontSee('Old Product');
});

it('shows top 3 products per category', function () {
    $category = Category::factory()->create(['name' => 'Popular Category']);

    $products = collect();
    for ($i = 1; $i <= 5; $i++) {
        $product = Product::factory()->create([
            'name' => "Product {$i}",
            'category_id' => $category->id,
            'price' => 100,
        ]);

        $order = Order::factory()->create([
            'status' => 'completed',
            'created_at' => now()->subDays(5),
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 10 - $i, // Higher quantity for earlier products
            'price' => 100,
        ]);

        $products->push($product);
    }

    Livewire::test(BestSellers::class)
        ->assertSuccessful()
        ->assertSee('Product 1')
        ->assertSee('Product 2')
        ->assertSee('Product 3')
        ->assertDontSee('Product 4')
        ->assertDontSee('Product 5');
});

it('aggregates quantities correctly for multiple orders of same product', function () {
    $category = Category::factory()->create(['name' => 'Test Category']);
    $product = Product::factory()->create([
        'name' => 'Multi Order Product',
        'category_id' => $category->id,
        'price' => 100,
    ]);

    // Create 3 different completed orders with the same product
    for ($i = 1; $i <= 3; $i++) {
        $order = Order::factory()->create([
            'status' => 'completed',
            'created_at' => now()->subDays($i),
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'price' => 100,
        ]);
    }

    Livewire::test(BestSellers::class)
        ->assertSuccessful()
        ->assertSee('Multi Order Product')
        ->assertSee('15') // 5 * 3 orders
        ->assertSee('1,500'); // 15 * 100
});

it('can refresh data', function () {
    Livewire::test(BestSellers::class)
        ->assertSuccessful()
        ->call('refreshData')
        ->assertSuccessful();
});
