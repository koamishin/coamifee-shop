<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\IngredientInventory;
use App\Models\Product;
use App\Models\ProductIngredient;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('ingredient creation and inventory tracking', function (): void {
    $ingredient = Ingredient::query()->create([
        'name' => 'Test Coffee Beans',
        'unit_type' => 'grams',
    ]);

    expect($ingredient)->toBeInstanceOf(Ingredient::class);

    $inventory = IngredientInventory::factory()->create([
        'ingredient_id' => $ingredient->id,
        'current_stock' => 1000,
        'min_stock_level' => 100,
        'max_stock_level' => 5000,
        'location' => 'Main Storage',
    ]);

    expect($inventory)->toBeInstanceOf(IngredientInventory::class);
});

test('product recipe ingredients', function (): void {
    $category = Category::factory()->create(['name' => 'Coffee']);
    $product = Product::query()->create([
        'name' => 'Test Latte',
        'price' => '4.0',
        'category_id' => $category->id,
        'preparation_time' => 5,
    ]);

    $ingredient = Ingredient::query()->create([
        'name' => 'Test Milk',
        'unit_type' => 'ml',
    ]);

    $productIngredient = ProductIngredient::query()->create([
        'product_id' => $product->id,
        'ingredient_id' => $ingredient->id,
        'quantity_required' => '200',
    ]);

    expect($productIngredient->quantity_required)->toBe('200.000');
    expect((float) $product->price)->toBe(4.0);
    expect($productIngredient->product_id)->toBe($product->id);
    expect($productIngredient->ingredient_id)->toBe($ingredient->id);
});

test('inventory service stock decrease', function (): void {
    $ingredient = Ingredient::query()->create([
        'name' => 'Test Coffee',
        'unit_type' => 'grams',
    ]);

    $inventory = IngredientInventory::factory()->create([
        'ingredient_id' => $ingredient->id,
        'current_stock' => 1000,
        'min_stock_level' => 100,
        'max_stock_level' => 5000,
        'location' => 'Main Storage',
    ]);

    $inventoryService = new InventoryService();

    // Test decreasing stock
    $result = $inventoryService->decreaseIngredientStock($ingredient, 100);

    expect($result)->toBeTrue();
    expect($inventory->fresh()->current_stock)->toBe('900.000');

    // Verify transaction was created
    $this->assertDatabaseHas('inventory_transactions', [
        'ingredient_id' => $ingredient->id,
        'transaction_type' => 'usage',
        'quantity_change' => '-100.000',
        'previous_stock' => '1000.000',
        'new_stock' => '900.000',
        'reason' => 'Order processing',
    ]);
});
