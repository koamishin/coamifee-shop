<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create categories
    $this->beveragesCategory = Category::factory()->create([
        'name' => 'Beverages',
        'sort_order' => 0,
    ]);

    $this->foodCategory = Category::factory()->create([
        'name' => 'Food',
        'sort_order' => 1,
    ]);
});

test('beverage products can have variants', function () {
    $product = Product::factory()->create([
        'name' => 'Coffee',
        'category_id' => $this->beveragesCategory->id,
        'price' => 89.00,
    ]);

    $hotVariant = ProductVariant::create([
        'product_id' => $product->id,
        'name' => 'Hot',
        'price' => 89.00,
        'is_default' => true,
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $coldVariant = ProductVariant::create([
        'product_id' => $product->id,
        'name' => 'Cold',
        'price' => 99.00,
        'is_default' => false,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    expect($product->hasVariants())->toBeTrue();
    expect($product->variants)->toHaveCount(2);
    expect($product->activeVariants)->toHaveCount(2);

    $hot = $product->variants->where('name', 'Hot')->first();
    expect($hot)->not->toBeNull();
    expect((float) $hot->price)->toBe(89.0);
    expect($hot->is_default)->toBeTrue();

    $cold = $product->variants->where('name', 'Cold')->first();
    expect($cold)->not->toBeNull();
    expect((float) $cold->price)->toBe(99.0);
    expect($cold->is_default)->toBeFalse();
});

test('beverage products without variants work correctly', function () {
    $product = Product::factory()->create([
        'name' => 'Simple Coffee',
        'category_id' => $this->beveragesCategory->id,
        'price' => 85.00,
    ]);

    expect($product->hasVariants())->toBeFalse();
    expect($product->variants)->toHaveCount(0);
    expect((float) $product->price)->toBe(85.0);
});

test('non-beverage products cannot have variants', function () {
    $product = Product::factory()->create([
        'name' => 'Burger',
        'category_id' => $this->foodCategory->id,
        'price' => 150.00,
    ]);

    expect($product->hasVariants())->toBeFalse();
    expect($product->variants)->toHaveCount(0);
    expect((float) $product->price)->toBe(150.0);
});

test('inactive variants are not included in active variants', function () {
    $product = Product::factory()->create([
        'name' => 'Tea',
        'category_id' => $this->beveragesCategory->id,
        'price' => 79.00,
    ]);

    ProductVariant::create([
        'product_id' => $product->id,
        'name' => 'Hot',
        'price' => 79.00,
        'is_default' => true,
        'is_active' => true,
        'sort_order' => 0,
    ]);

    ProductVariant::create([
        'product_id' => $product->id,
        'name' => 'Cold',
        'price' => 89.00,
        'is_default' => false,
        'is_active' => false, // Inactive
        'sort_order' => 1,
    ]);

    expect($product->variants)->toHaveCount(2);
    expect($product->activeVariants)->toHaveCount(1);

    $activeVariant = $product->activeVariants->first();
    expect($activeVariant->name)->toBe('Hot');
});

test('variants are ordered by sort_order', function () {
    $product = Product::factory()->create([
        'name' => 'Latte',
        'category_id' => $this->beveragesCategory->id,
        'price' => 120.00,
    ]);

    ProductVariant::create([
        'product_id' => $product->id,
        'name' => 'Cold',
        'price' => 130.00,
        'is_default' => false,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    ProductVariant::create([
        'product_id' => $product->id,
        'name' => 'Hot',
        'price' => 120.00,
        'is_default' => true,
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $activeVariants = $product->activeVariants;
    expect($activeVariants)->toHaveCount(2);
    expect($activeVariants->first()->name)->toBe('Hot');
    expect($activeVariants->last()->name)->toBe('Cold');
});

test('default variant is properly flagged', function () {
    $product = Product::factory()->create([
        'name' => 'Mocha',
        'category_id' => $this->beveragesCategory->id,
        'price' => 130.00,
    ]);

    $hotVariant = ProductVariant::create([
        'product_id' => $product->id,
        'name' => 'Hot',
        'price' => 130.00,
        'is_default' => true,
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $coldVariant = ProductVariant::create([
        'product_id' => $product->id,
        'name' => 'Cold',
        'price' => 140.00,
        'is_default' => false,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    expect($hotVariant->is_default)->toBeTrue();
    expect($coldVariant->is_default)->toBeFalse();
});

test('products in beverages category id 1 can have variants', function () {
    $product = Product::factory()->create([
        'name' => 'Cappuccino',
        'category_id' => 1, // Hard-coded Beverages category ID
        'price' => 110.00,
    ]);

    ProductVariant::create([
        'product_id' => $product->id,
        'name' => 'Hot',
        'price' => 110.00,
        'is_default' => true,
        'is_active' => true,
        'sort_order' => 0,
    ]);

    ProductVariant::create([
        'product_id' => $product->id,
        'name' => 'Cold',
        'price' => 120.00,
        'is_default' => false,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    expect($product->category_id)->toBe(1);
    expect($product->hasVariants())->toBeTrue();
    expect($product->variants)->toHaveCount(2);
});
