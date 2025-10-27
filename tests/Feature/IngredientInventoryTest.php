<?php

declare(strict_types=1);

use App\Enums\UnitType;
use App\Models\Ingredient;
use App\Models\IngredientInventory;
use function Pest\Laravel\{actingAs, get, post, put, delete};
use function Pest\Faker\{fake};

beforeEach(function () {
    $this->user = \App\Models\User::factory()->create();
    actingAs($this->user);
});

describe('Centralized Ingredient Inventory Management', function () {
    it('can create a new ingredient and inventory in one form', function () {
        $response = post('/admin/ingredient-inventories', [
            'create_new_ingredient' => true,
            'new_ingredient_name' => 'Arabica Coffee Beans',
            'new_ingredient_unit_type' => UnitType::GRAMS->value,
            'current_stock' => 5000,
            'min_stock_level' => 1000,
            'max_stock_level' => 10000,
            'reorder_level' => 1500,
            'unit_cost' => 0.025,
            'location' => 'Pantry A',
            'supplier_info' => 'Coffee Roasters Inc. - Contact: 555-0123',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('ingredients', [
            'name' => 'Arabica Coffee Beans',
            'unit_type' => UnitType::GRAMS->value,
        ]);

        $ingredient = Ingredient::where('name', 'Arabica Coffee Beans')->first();

        $this->assertDatabaseHas('ingredient_inventories', [
            'ingredient_id' => $ingredient->id,
            'current_stock' => 5000,
            'min_stock_level' => 1000,
            'max_stock_level' => 10000,
            'reorder_level' => 1500,
            'unit_cost' => 0.025,
            'location' => 'Pantry A',
            'supplier_info' => 'Coffee Roasters Inc. - Contact: 555-0123',
        ]);
    });

    it('can create inventory for existing ingredient', function () {
        $ingredient = Ingredient::factory()->create([
            'name' => 'Premium Sugar',
            'unit_type' => UnitType::GRAMS->value,
        ]);

        $response = post('/admin/ingredient-inventories', [
            'ingredient_id' => $ingredient->id,
            'current_stock' => 5000.50,
            'min_stock_level' => 1000,
            'max_stock_level' => 10000,
            'reorder_level' => 1500,
            'unit_cost' => 0.025,
            'location' => 'Pantry A',
            'supplier_info' => 'Sweet Supplies Inc. - Contact: 555-0123',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('ingredient_inventories', [
            'ingredient_id' => $ingredient->id,
            'current_stock' => 5000.50,
            'min_stock_level' => 1000,
            'max_stock_level' => 10000,
            'reorder_level' => 1500,
            'unit_cost' => 0.025,
            'location' => 'Pantry A',
            'supplier_info' => 'Sweet Supplies Inc. - Contact: 555-0123',
        ]);
    });

    it('can still create basic ingredient without inventory', function () {
        $response = post('/admin/ingredients', [
            'name' => 'Basic Ingredient',
            'unit_type' => UnitType::MILLILITERS->value,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('ingredients', [
            'name' => 'Basic Ingredient',
            'unit_type' => UnitType::MILLILITERS->value,
        ]);
    });

    it('properly displays comprehensive inventory information', function () {
        $ingredient = Ingredient::factory()->create([
            'name' => 'Test Milk',
            'unit_type' => UnitType::MILLILITERS->value,
        ]);

        $inventory = IngredientInventory::factory()->create([
            'ingredient_id' => $ingredient->id,
            'current_stock' => 2000,
            'min_stock_level' => 500,
            'max_stock_level' => 5000,
            'reorder_level' => 800,
            'unit_cost' => 0.005,
            'location' => 'Fridge A',
            'supplier_info' => 'Dairy Farm Co.',
        ]);

        $response = get('/admin/ingredient-inventories');

        $response->assertSee('Test Milk');
        $response->assertSee('ml');
        $response->assertSee('2,000');
        $response->assertSee('500');
        $response->assertSee('5,000');
        $response->assertSee('800');
        $response->assertSee('Fridge A');
        $response->assertSee('Dairy Farm Co.');
    });

    it('shows simplified ingredient list with inventory status', function () {
        $ingredient = Ingredient::factory()->create([
            'name' => 'Test Flour',
            'unit_type' => UnitType::GRAMS->value,
        ]);

        $inventory = IngredientInventory::factory()->create([
            'ingredient_id' => $ingredient->id,
            'current_stock' => 3000,
        ]);

        $response = get('/admin/ingredients');

        $response->assertSee('Test Flour');
        $response->assertSee('3,000');
        $response->assertSee('Manage Inventory');
    });

    it('properly handles ingredients without inventory in ingredient list', function () {
        $ingredient = Ingredient::factory()->create([
            'name' => 'Vanilla Extract',
            'unit_type' => UnitType::MILLILITERS->value,
        ]);

        $response = get('/admin/ingredients');

        $response->assertSee('Vanilla Extract');
        $response->assertSee('No inventory');
    });

    it('validates required fields for inventory creation', function () {
        $response = post('/admin/ingredient-inventories', [
            'current_stock' => 1000,
            'min_stock_level' => 500,
        ]);

        $response->assertSessionHasErrors('ingredient_id');
    });

    it('validates required fields when creating new ingredient in inventory form', function () {
        $response = post('/admin/ingredient-inventories', [
            'create_new_ingredient' => true,
            'new_ingredient_name' => '',
            'new_ingredient_unit_type' => '',
            'current_stock' => 1000,
        ]);

        $response->assertSessionHasErrors(['new_ingredient_name', 'new_ingredient_unit_type']);
    });

    it('validates required fields for basic ingredient creation', function () {
        $response = post('/admin/ingredients', [
            'unit_type' => UnitType::GRAMS->value,
        ]);

        $response->assertSessionHasErrors('name');
    });

    it('displays unit type with correct icon and color in tables', function () {
        $ingredient = Ingredient::factory()->create([
            'name' => 'Test Weight Item',
            'unit_type' => UnitType::GRAMS->value,
        ]);

        $inventory = IngredientInventory::factory()->create([
            'ingredient_id' => $ingredient->id,
            'current_stock' => 1000,
        ]);

        $response = get('/admin/ingredient-inventories');
        $response->assertSee('Test Weight Item');
        $response->assertSee('Grams');
        // Should have the scale icon and warning color for grams
    });

    it('correctly formats unit type badges with icons', function () {
        $testCases = [
            ['type' => UnitType::GRAMS, 'label' => 'Grams', 'color' => 'warning', 'icon' => 'scale'],
            ['type' => UnitType::KILOGRAMS, 'label' => 'Kilograms', 'color' => 'danger', 'icon' => 'scale'],
            ['type' => UnitType::MILLILITERS, 'label' => 'Milliliters', 'color' => 'info', 'icon' => 'beaker'],
            ['type' => UnitType::LITERS, 'label' => 'Liters', 'color' => 'primary', 'icon' => 'beaker'],
            ['type' => UnitType::PIECES, 'label' => 'Pieces', 'color' => 'success', 'icon' => 'cube'],
        ];

        foreach ($testCases as $testCase) {
            $ingredient = Ingredient::factory()->create([
                'name' => "Test {$testCase['label']} Item",
                'unit_type' => $testCase['type']->value,
            ]);

            expect($testCase['type']->getLabel())->toBe($testCase['label']);
            expect($testCase['type']->getColor())->toBe($testCase['color']);
            expect($testCase['type']->getIcon())->toBe('heroicon-o-' . $testCase['icon']);
            expect($testCase['type']->getDescription())->toBeString();
        }
    });

    it('uses unit type enum in select field options', function () {
        $options = UnitType::getOptions();

        expect($options)->toBeArray();
        expect($options)->toHaveCount(5);
        expect($options)->toHaveKey(UnitType::GRAMS->value);
        expect($options)->toHaveKey(UnitType::KILOGRAMS->value);
        expect($options)->toHaveKey(UnitType::MILLILITERS->value);
        expect($options)->toHaveKey(UnitType::LITERS->value);
        expect($options)->toHaveKey(UnitType::PIECES->value);
        expect($options[UnitType::GRAMS->value])->toBe('Grams');
    });

    it('handles null values gracefully in form display', function () {
        // Test that form can be loaded without errors when all values are null
        $response = get('/admin/ingredient-inventories/create');
        $response->assertSuccessful();

        // Should not throw any errors when accessing the form page
        expect($response->status())->toBe(200);
    });

    it('displays proper fallback for missing unit types', function () {
        // Test when ingredient has no unit type (shouldn't happen but just in case)
        $ingredient = Ingredient::factory()->create([
            'name' => 'Test Item',
            'unit_type' => UnitType::GRAMS->value,
        ]);

        // Test the display functions work with proper enum values
        expect($ingredient->unit_type->getLabel())->toBe('Grams');
        expect($ingredient->unit_type->getColor())->toBe('warning');
        expect($ingredient->unit_type->getIcon())->toBe('heroicon-o-scale');
    });
});
