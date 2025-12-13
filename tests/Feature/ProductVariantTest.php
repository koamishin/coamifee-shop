<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('products can have variants', function (): void {
    $category = Category::factory()->create(['name' => 'Beverages']);

    $product = Product::factory()->create([
        'name' => 'Coffee',
        'category_id' => $category->id,
        'price' => 100,
    ]);

    $hotVariant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'name' => 'Hot',
        'price' => 100,
        'is_default' => true,
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $coldVariant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'name' => 'Cold',
        'price' => 120,
        'is_default' => false,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    expect($product->variants)->toHaveCount(2);
    expect($product->hasVariants())->toBeTrue();
    expect($product->activeVariants)->toHaveCount(2);
});

test('product can check if it has variants', function (): void {
    $product = Product::factory()->create();

    expect($product->hasVariants())->toBeFalse();

    ProductVariant::query()->create([
        'product_id' => $product->id,
        'name' => 'Large',
        'price' => 150,
        'is_default' => true,
        'is_active' => true,
    ]);

    $product->refresh();
    expect($product->hasVariants())->toBeTrue();
});

test('variants have correct relationships', function (): void {
    $product = Product::factory()->create();

    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'name' => 'Medium',
        'price' => 100,
        'is_default' => true,
        'is_active' => true,
    ]);

    expect($variant->product->id)->toBe($product->id);
    expect($product->variants->first()->id)->toBe($variant->id);
});
