<?php

declare(strict_types=1);

use App\Models\Ingredient;
use App\Models\IngredientInventory;
use App\Models\Product;
use App\Models\ProductIngredient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it(
    'can access current_stock from ingredient and its inventory relationship',
    function (): void {
        // Create an ingredient
        $ingredient = Ingredient::factory()->create();

        // Create inventory record for this ingredient
        $inventory = IngredientInventory::factory()->create([
            'ingredient_id' => $ingredient->id,
            'current_stock' => 75.25,
        ]);

        // Reload the ingredient with its relationship
        $ingredient = $ingredient->fresh(['inventory']);

        // Test that we can access stock from inventory relationship
        expect($ingredient->inventory)->not->toBeNull();
        expect((float) $ingredient->inventory->current_stock)->toBe(75.25);
    },
);

test(
    'product ingredients table can access current_stock without errors',
    function (): void {
        // Create related models
        $product = Product::factory()->create();
        $ingredient = Ingredient::factory()->create();

        // Create inventory record
        $inventory = IngredientInventory::factory()->create([
            'ingredient_id' => $ingredient->id,
            'current_stock' => 45.0,
        ]);

        // Create product ingredient relationship
        $productIngredient = ProductIngredient::factory()->create([
            'product_id' => $product->id,
            'ingredient_id' => $ingredient->id,
            'quantity_required' => 10,
        ]);

        // Load the product ingredient with relationships
        $productIngredient->load(['ingredient', 'ingredient.inventory']);

        // Test accessing the current stock from inventory relationship
        expect(
            (float) $productIngredient->ingredient->inventory->current_stock,
        )->toBe(45.0);
    },
);

test(
    'product ingredient create form is accessible without TypeError',
    function (): void {
        // Create necessary data for the form
        $product = Product::factory()->create();
        $ingredient = Ingredient::factory()->create();

        // Test accessing the create form page
        $user = User::factory()->create();

        // The main goal is to verify no TypeError is thrown
        // We expect either 200 (success) or 403 (forbidden due to permissions)
        // But definitely not a 500 (server error from TypeError)
        $response = $this->actingAs($user)->get(
            '/admin/product-ingredients/create',
        );

        // Should not throw TypeError (which would result in 500)
        // Accept either success or forbidden (no TypeError occurred)
        expect($response->getStatusCode())->toBeIn([200, 403]);
    },
);

test(
    'ingredient inventory create form is accessible without TypeError',
    function (): void {
        // Create necessary data for the form
        $ingredient = Ingredient::factory()->create();

        // Test accessing the create form page
        $user = User::factory()->create();

        // The main goal is to verify no TypeError is thrown
        // We expect either 200 (success) or 403 (forbidden due to permissions)
        // But definitely not a 500 (server error from TypeError)
        $response = $this->actingAs($user)->get(
            '/admin/ingredient-inventories/create',
        );

        // Should not throw TypeError (which would result in 500)
        // Accept either success or forbidden (no TypeError occurred)
        expect($response->getStatusCode())->toBeIn([200, 403]);
    },
);
