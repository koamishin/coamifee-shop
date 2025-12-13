<?php

declare(strict_types=1);

use App\Models\Ingredient;
use App\Models\IngredientInventory;
use App\Models\Product;
use App\Models\ProductIngredient;
use App\Services\PosService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('computes product availability and stock status from eager loaded inventory', function (): void {
    $inStockProduct = Product::factory()->create(['is_active' => true]);
    $inStockIngredient = Ingredient::factory()->create();
    IngredientInventory::factory()->create([
        'ingredient_id' => $inStockIngredient->id,
        'current_stock' => 100,
    ]);
    ProductIngredient::factory()->create([
        'product_id' => $inStockProduct->id,
        'ingredient_id' => $inStockIngredient->id,
        'quantity_required' => 10,
    ]);

    $lowStockProduct = Product::factory()->create(['is_active' => true]);
    $lowStockIngredient = Ingredient::factory()->create();
    IngredientInventory::factory()->create([
        'ingredient_id' => $lowStockIngredient->id,
        'current_stock' => 20,
    ]);
    ProductIngredient::factory()->create([
        'product_id' => $lowStockProduct->id,
        'ingredient_id' => $lowStockIngredient->id,
        'quantity_required' => 10,
    ]);

    $outOfStockProduct = Product::factory()->create(['is_active' => true]);
    $outOfStockIngredient = Ingredient::factory()->create();
    IngredientInventory::factory()->create([
        'ingredient_id' => $outOfStockIngredient->id,
        'current_stock' => 5,
    ]);
    ProductIngredient::factory()->create([
        'product_id' => $outOfStockProduct->id,
        'ingredient_id' => $outOfStockIngredient->id,
        'quantity_required' => 10,
    ]);

    $noRecipeProduct = Product::factory()->create(['is_active' => true]);

    $products = Product::query()
        ->whereKey([
            $inStockProduct->id,
            $lowStockProduct->id,
            $outOfStockProduct->id,
            $noRecipeProduct->id,
        ])
        ->with(['ingredients.ingredient.inventory'])
        ->get();

    $availability = resolve(PosService::class)->updateProductAvailability($products);

    expect($availability[$inStockProduct->id])
        ->toMatchArray([
            'can_produce' => true,
            'max_quantity' => 10,
            'stock_status' => 'in_stock',
        ]);

    expect($availability[$lowStockProduct->id])
        ->toMatchArray([
            'can_produce' => true,
            'max_quantity' => 2,
            'stock_status' => 'low_stock',
        ]);

    expect($availability[$outOfStockProduct->id])
        ->toMatchArray([
            'can_produce' => false,
            'max_quantity' => 0,
            'stock_status' => 'out_of_stock',
        ]);

    expect($availability[$noRecipeProduct->id])
        ->toMatchArray([
            'can_produce' => true,
            'max_quantity' => 999,
            'stock_status' => 'in_stock',
        ]);
});
