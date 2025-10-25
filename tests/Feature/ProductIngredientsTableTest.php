<?php

declare(strict_types=1);

use App\Models\Ingredient;
use App\Models\IngredientInventory;
use App\Models\Product;
use App\Models\ProductIngredient;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can access current_stock from ingredient and its inventory relationship', function () {
    // Create an ingredient
    $ingredient = Ingredient::factory()->create([
        'current_stock' => 100.50,
        'is_trackable' => true,
    ]);

    // Create inventory record for this ingredient
    $inventory = IngredientInventory::factory()->create([
        'ingredient_id' => $ingredient->id,
        'current_stock' => 75.25,
    ]);

    // Reload the ingredient with its relationship
    $ingredient = $ingredient->fresh(['inventory']);

    // Test that we can access both stock values
    expect((float) $ingredient->current_stock)->toBe(100.50);
    expect($ingredient->inventory)->not->toBeNull();
    expect((float) $ingredient->inventory->current_stock)->toBe(75.25);
});

test('product ingredients table can access current_stock without errors', function () {
    // Create related models
    $product = Product::factory()->create();
    $ingredient = Ingredient::factory()->create([
        'current_stock' => 50.75,
        'is_trackable' => true,
    ]);

    // Create inventory record
    $inventory = IngredientInventory::factory()->create([
        'ingredient_id' => $ingredient->id,
        'current_stock' => 45.00,
    ]);

    // Create product ingredient relationship
    $productIngredient = ProductIngredient::factory()->create([
        'product_id' => $product->id,
        'ingredient_id' => $ingredient->id,
        'quantity_required' => 10,
    ]);

    // Load the product ingredient with relationships
    $productIngredient->load(['ingredient', 'ingredient.inventory']);

    // Test accessing the current stock values
    expect((float) $productIngredient->ingredient->current_stock)->toBe(50.75);
    expect((float) $productIngredient->ingredient->inventory->current_stock)->toBe(45.00);
});
