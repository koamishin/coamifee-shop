<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a category using factory', function () {
    $category = Category::factory()->create();

    expect($category)->toBeInstanceOf(Category::class);
    expect($category->name)->not->toBeEmpty();
    expect($category->slug)->not->toBeEmpty();
    expect($category->is_active)->toBeTrue();
    expect($category->sort_order)->toBeGreaterThanOrEqual(1);
});

it('can create a product using factory', function () {
    $product = Product::factory()->create();

    expect($product)->toBeInstanceOf(Product::class);
    expect($product->name)->not->toBeEmpty();
    expect($product->slug)->not->toBeEmpty();
    expect($product->price)->toBeGreaterThan(0);
    expect($product->cost)->toBeGreaterThan(0);
    expect($product->sku)->not->toBeEmpty();
    expect($product->is_active)->toBeTrue();
    expect($product->category_id)->not->toBeNull();
});

it('can create a customer using factory', function () {
    $customer = Customer::factory()->create();

    expect($customer)->toBeInstanceOf(Customer::class);
    expect($customer->first_name)->not->toBeEmpty();
    expect($customer->last_name)->not->toBeEmpty();
    expect($customer->email)->not->toBeEmpty();
    expect($customer->full_name)->toBe($customer->first_name.' '.$customer->last_name);
    expect($customer->is_active)->toBeTrue();
});

it('can create inventory using factory', function () {
    $product = Product::factory()->create();
    $inventory = Inventory::factory()->create(['product_id' => $product->id]);

    expect($inventory)->toBeInstanceOf(Inventory::class);
    expect($inventory->product_id)->toBe($product->id);
    expect($inventory->quantity)->toBeGreaterThanOrEqual(10);
    expect($inventory->minimum_stock)->toBeGreaterThanOrEqual(5);
    expect($inventory->unit_cost)->toBeGreaterThan(0);
    expect($inventory->location)->not->toBeEmpty();
});

it('can create product with inventory relationship', function () {
    $product = Product::factory()
        ->has(Inventory::factory(), 'inventory')
        ->create();

    expect($product->inventory)->not->toBeNull();
    expect($product->inventory->product_id)->toBe($product->id);
    expect($product->isInStock())->toBeTrue();
});

it('can create category with products', function () {
    $category = Category::factory()
        ->has(Product::factory(3), 'products')
        ->create();

    expect($category->products)->toHaveCount(3);
    expect($category->activeProducts()->get())->toHaveCount(3);

    foreach ($category->products as $product) {
        expect($product->category_id)->toBe($category->id);
    }
});

it('product factory generates valid profit margin', function () {
    $product = Product::factory()->create();

    expect($product->profit_margin)->toBeGreaterThan(0);
    expect($product->profit_margin)->toBeLessThan(100);
});

it('customer factory generates realistic data', function () {
    $customer = Customer::factory()->create();

    expect($customer->email)->toContain('@');
    expect($customer->birth_date)->toBeInstanceOf(Carbon\Carbon::class);
    expect($customer->loyalty_points)->toBeGreaterThanOrEqual(0);
    expect($customer->preferences)->toBeArray();
});

it('inventory factory generates correct stock status', function () {
    $product = Product::factory()->create();
    $inventory = Inventory::factory()->create([
        'product_id' => $product->id,
        'quantity' => 50,
        'minimum_stock' => 10,
        'maximum_stock' => 100,
    ]);

    expect($inventory->isLowStock())->toBeFalse();
    expect($inventory->isOverstock())->toBeFalse();
    expect($inventory->isOptimalStock())->toBeTrue();
    expect($inventory->stock_status)->toBe('in_stock');
});

it('low stock inventory is detected correctly', function () {
    $product = Product::factory()->create();
    $inventory = Inventory::factory()->create([
        'product_id' => $product->id,
        'quantity' => 5,
        'minimum_stock' => 10,
    ]);

    expect($inventory->isLowStock())->toBeTrue();
    expect($inventory->stock_status)->toBe('low_stock');
});

it('out of stock inventory is detected correctly', function () {
    $product = Product::factory()->create();
    $inventory = Inventory::factory()->create([
        'product_id' => $product->id,
        'quantity' => 0,
        'minimum_stock' => 10,
    ]);

    expect($inventory->isLowStock())->toBeTrue();
    expect($inventory->stock_status)->toBe('out_of_stock');
});
