<?php

declare(strict_types=1);

use App\Models\Ingredient;
use App\Models\IngredientInventory;
use App\Models\Product;
use App\Models\ProductIngredient;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('ingredient creation and inventory tracking', function () {
    $ingredient = Ingredient::create([
        'name' => 'Test Coffee Beans',
        'unit_type' => 'grams',
        'is_trackable' => true,
        'current_stock' => 1000,
        'unit_cost' => 0.02,
    ]);

    expect($ingredient)->toBeInstanceOf(Ingredient::class);
    expect($ingredient->is_trackable)->toBeTrue();

    $inventory = $ingredient->inventory()->first();
    expect($inventory)->toBeInstanceOf(IngredientInventory::class);
});

test('product recipe ingredients', function () {
    $product = Product::create([
        'name' => 'Test Latte',
        'price' => 4.00,
        'category_id' => 1,
        'preparation_time' => 5,
    ]);

    $ingredient = Ingredient::create([
        'name' => 'Test Milk',
        'unit_type' => 'ml',
        'is_trackable' => true,
        'current_stock' => 2000,
        'unit_cost' => 0.001,
    ]);

    $productIngredient = ProductIngredient::create([
        'product_id' => $product->id,
        'ingredient_id' => $ingredient->id,
        'quantity_required' => 200,
    ]);

    expect($productIngredient->quantity_required)->toBe(200);
    expect($productIngredient->product_id)->toBe($product->id);
    expect($productIngredient->ingredient_id)->toBe($ingredient->id);
});

test('inventory service stock decrease', function () {
    $ingredient = Ingredient::create([
        'name' => 'Test Coffee',
        'unit_type' => 'grams',
        'is_trackable' => true,
        'current_stock' => 1000,
        'unit_cost' => 0.02,
    ]);

    $inventory = IngredientInventory::create([
        'ingredient_id' => $ingredient->id,
        'current_stock' => 1000,
        'min_stock_level' => 100,
        'max_stock_level' => 5000,
        'location' => 'Main Storage',
    ]);

    $service = new InventoryService();
    $result = $service->decreaseIngredientStock($ingredient, 100);

    expect($result)->toBeTrue();
    expect($inventory->fresh()->current_stock)->toBe(900);

    $transaction = $ingredient->transactions()->first();
    expect($transaction->transaction_type)->toBe('usage');
    expect($transaction->quantity_change)->toBe(-100);
});
