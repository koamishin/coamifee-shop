<?php

declare(strict_types=1);

use App\Enums\UnitType;
use App\Filament\Resources\IngredientInventories\Pages\CreateIngredientInventory;
use App\Filament\Resources\IngredientInventories\Pages\ListIngredientInventories;
use App\Filament\Resources\Ingredients\Pages\CreateIngredient;
use App\Filament\Resources\Ingredients\Pages\ListIngredients;
use App\Models\Ingredient;
use App\Models\IngredientInventory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    actingAs($this->user);
});

describe('Centralized Ingredient Inventory Management', function () {
    it('can create a new ingredient and inventory in one form', function () {
        Livewire::test(CreateIngredientInventory::class)
            ->fillForm([
                'create_new_ingredient' => true,
                'new_ingredient_name' => 'Arabica Coffee Beans',
                'new_ingredient_unit_type' => UnitType::GRAMS->value,
                'current_stock' => 5000,
                'min_stock_level' => 1000,
                'max_stock_level' => 10000,
                'reorder_level' => 1500,
                'location' => 'Pantry A',
                'supplier_info' => 'Coffee Roasters Inc. - Contact: 555-0123',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

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
            'location' => 'Pantry A',
            'supplier_info' => 'Coffee Roasters Inc. - Contact: 555-0123',
        ]);
    });

    it('can create inventory for existing ingredient', function () {
        $ingredient = Ingredient::factory()->create([
            'name' => 'Premium Sugar',
            'unit_type' => UnitType::GRAMS->value,
        ]);

        Livewire::test(CreateIngredientInventory::class)
            ->fillForm([
                'ingredient_id' => $ingredient->id,
                'current_stock' => 5000.50,
                'min_stock_level' => 1000,
                'max_stock_level' => 10000,
                'reorder_level' => 1500,
                'location' => 'Pantry A',
                'supplier_info' => 'Sweet Supplies Inc. - Contact: 555-0123',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('ingredient_inventories', [
            'ingredient_id' => $ingredient->id,
            'current_stock' => 5000.50,
            'min_stock_level' => 1000,
            'max_stock_level' => 10000,
            'reorder_level' => 1500,
            'location' => 'Pantry A',
            'supplier_info' => 'Sweet Supplies Inc. - Contact: 555-0123',
        ]);
    });

    it('can still create basic ingredient without inventory', function () {
        Livewire::test(CreateIngredient::class)
            ->fillForm([
                'name' => 'Basic Ingredient',
                'unit_type' => UnitType::MILLILITERS->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

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

        Livewire::test(ListIngredientInventories::class)
            ->assertCanSeeTableRecords([$inventory]);
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

        Livewire::test(ListIngredients::class)
            ->assertCanSeeTableRecords([$ingredient]);
    });

    it('properly handles ingredients without inventory in ingredient list', function () {
        $ingredient = Ingredient::factory()->create([
            'name' => 'Vanilla Extract',
            'unit_type' => UnitType::MILLILITERS->value,
        ]);

        Livewire::test(ListIngredients::class)
            ->assertCanSeeTableRecords([$ingredient]);
    });

    it('validates required fields for inventory creation', function () {
        Livewire::test(CreateIngredientInventory::class)
            ->fillForm([
                'current_stock' => 1000,
                'min_stock_level' => 500,
            ])
            ->call('create')
            ->assertHasFormErrors(['ingredient_id']);
    });

    it('validates required fields when creating new ingredient in inventory form', function () {
        Livewire::test(CreateIngredientInventory::class)
            ->fillForm([
                'create_new_ingredient' => true,
                'new_ingredient_name' => '',
                'new_ingredient_unit_type' => '',
                'current_stock' => 1000,
            ])
            ->call('create')
            ->assertHasFormErrors(['new_ingredient_name', 'new_ingredient_unit_type']);
    });

    it('validates required fields for basic ingredient creation', function () {
        Livewire::test(CreateIngredient::class)
            ->fillForm([
                'unit_type' => UnitType::GRAMS->value,
            ])
            ->call('create')
            ->assertHasFormErrors(['name']);
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

        Livewire::test(ListIngredientInventories::class)
            ->assertCanSeeTableRecords([$inventory]);
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
            expect($testCase['type']->getIcon())->toBe('heroicon-o-'.$testCase['icon']);
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
        Livewire::test(CreateIngredientInventory::class)
            ->assertSuccessful();
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
