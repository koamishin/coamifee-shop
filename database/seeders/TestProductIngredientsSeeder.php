<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\IngredientInventory;
use App\Models\Product;
use App\Models\ProductIngredient;
use Illuminate\Database\Seeder;

final class TestProductIngredientsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating test product ingredients for enhanced UI testing...');

        // Get or create test ingredients with different unit types
        $ingredients = $this->createTestIngredients();

        // Get or create test products
        $products = $this->createTestProducts();

        // Create comprehensive product-ingredient relationships
        $this->createTestProductIngredients($products, $ingredients);

        $this->command->info('Test product ingredients created successfully!');
    }

    private function createTestIngredients(): array
    {
        $testIngredients = [
            ['name' => 'Test Wheat Flour', 'unit_type' => 'grams'],
            ['name' => 'Test Fresh Eggs', 'unit_type' => 'pieces'],
            ['name' => 'Test Whole Milk', 'unit_type' => 'liters'],
            ['name' => 'Test Butter', 'unit_type' => 'grams'],
            ['name' => 'Test Sugar', 'unit_type' => 'kilograms'],
            ['name' => 'Test Olive Oil', 'unit_type' => 'ml'],
            ['name' => 'Test Salt', 'unit_type' => 'grams'],
            ['name' => 'Test Black Pepper', 'unit_type' => 'grams'],
            ['name' => 'Test Chicken Breast', 'unit_type' => 'kilograms'],
            ['name' => 'Test Coffee Beans', 'unit_type' => 'grams'],
        ];

        $createdIngredients = [];
        foreach ($testIngredients as $ingredientData) {
            $ingredient = Ingredient::query()->firstOrCreate(
                ['name' => $ingredientData['name']],
                array_merge($ingredientData, [
                    'unit_type' => $ingredientData['unit_type'],
                ])
            );

            // Create inventory with realistic stock levels
            IngredientInventory::query()->firstOrCreate(
                ['ingredient_id' => $ingredient->id],
                [
                    'current_stock' => $this->getRealisticStock($ingredientData['unit_type']),
                    'min_stock_level' => $this->getMinStock($ingredientData['unit_type']),
                    'max_stock_level' => $this->getMaxStock($ingredientData['unit_type']),
                    'unit_cost' => $this->getRealisticCost($ingredientData['name']),
                    'location' => $this->getStorageLocation($ingredientData['unit_type']),
                    'supplier_info' => 'Test Supplier',
                    'last_restocked_at' => now()->subDays(rand(1, 15)),
                ]
            );

            $createdIngredients[$ingredientData['name']] = $ingredient;
        }

        return $createdIngredients;
    }

    private function createTestProducts(): array
    {
        // Ensure we have categories
        $foodCategory = Category::firstOrCreate(['name' => 'Test Foods'], [
            'description' => 'Test food products',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $beverageCategory = Category::firstOrCreate(['name' => 'Test Beverages'], [
            'description' => 'Test beverage products',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $testProducts = [
            ['name' => 'Test Pancake Special', 'category_id' => $foodCategory->id, 'price' => 125.00],
            ['name' => 'Test Chicken Supreme', 'category_id' => $foodCategory->id, 'price' => 189.00],
            ['name' => 'Test Coffee Deluxe', 'category_id' => $beverageCategory->id, 'price' => 95.00],
            ['name' => 'Test Sweet Treat', 'category_id' => $foodCategory->id, 'price' => 85.00],
        ];

        $createdProducts = [];
        foreach ($testProducts as $productData) {
            $product = Product::query()->firstOrCreate(
                ['name' => $productData['name']],
                array_merge($productData, [
                    'description' => "Test product for UI demonstration - {$productData['name']}",
                    'preparation_time' => rand(5, 20),
                    'is_active' => true,
                    'sku' => 'TEST-' . strtoupper(str_replace(' ', '-', $productData['name'])),
                ])
            );

            $createdProducts[$productData['name']] = $product;
        }

        return $createdProducts;
    }

    private function createTestProductIngredients(array $products, array $ingredients): void
    {
        // Define test recipes
        $testRecipes = [
            'Test Pancake Special' => [
                'Test Wheat Flour' => 250.0,
                'Test Fresh Eggs' => 2.0,
                'Test Whole Milk' => 0.3,
                'Test Butter' => 50.0,
                'Test Sugar' => 0.05,
            ],
            'Test Chicken Supreme' => [
                'Test Chicken Breast' => 0.25,
                'Test Salt' => 2.0,
                'Test Black Pepper' => 1.0,
                'Test Olive Oil' => 15.0,
            ],
            'Test Coffee Deluxe' => [
                'Test Coffee Beans' => 20.0,
                'Test Whole Milk' => 0.2,
                'Test Sugar' => 0.02,
            ],
            'Test Sweet Treat' => [
                'Test Wheat Flour' => 150.0,
                'Test Butter' => 75.0,
                'Test Sugar' => 0.08,
                'Test Fresh Eggs' => 1.0,
            ],
        ];

        foreach ($testRecipes as $productName => $recipe) {
            if (!isset($products[$productName])) {
                continue;
            }

            $product = $products[$productName];

            // Remove existing ingredients for this test product
            ProductIngredient::query()->where('product_id', $product->id)->delete();

            foreach ($recipe as $ingredientName => $quantity) {
                if (!isset($ingredients[$ingredientName])) {
                    $this->command->warn("Warning: Ingredient '{$ingredientName}' not found");
                    continue;
                }

                ProductIngredient::query()->create([
                    'product_id' => $product->id,
                    'ingredient_id' => $ingredients[$ingredientName]->id,
                    'quantity_required' => $quantity,
                ]);
            }
        }
    }

    private function getRealisticStock(string $unitType): float
    {
        return match ($unitType) {
            'grams' => rand(500, 5000),
            'kilograms' => rand(5, 50),
            'ml' => rand(500, 5000),
            'liters' => rand(1, 20),
            'pieces' => rand(20, 200),
            default => rand(100, 1000),
        };
    }

    private function getMinStock(string $unitType): int
    {
        return match ($unitType) {
            'grams' => 200,
            'kilograms' => 2,
            'ml' => 200,
            'liters' => 1,
            'pieces' => 10,
            default => 50,
        };
    }

    private function getMaxStock(string $unitType): int
    {
        return match ($unitType) {
            'grams' => 10000,
            'kilograms' => 100,
            'ml' => 10000,
            'liters' => 50,
            'pieces' => 500,
            default => 2000,
        };
    }

    private function getRealisticCost(string $ingredientName): float
    {
        $costs = [
            'Test Wheat Flour' => 0.002,
            'Test Fresh Eggs' => 0.015,
            'Test Whole Milk' => 0.002,
            'Test Butter' => 0.008,
            'Test Sugar' => 0.0015,
            'Test Olive Oil' => 0.006,
            'Test Salt' => 0.001,
            'Test Black Pepper' => 0.010,
            'Test Chicken Breast' => 0.018,
            'Test Coffee Beans' => 0.050,
        ];

        return $costs[$ingredientName] ?? 0.005;
    }

    private function getStorageLocation(string $unitType): string
    {
        return match ($unitType) {
            'grams', 'kilograms' => 'Dry Storage',
            'ml', 'liters' => 'Fridge',
            'pieces' => 'Fridge',
            default => 'Main Storage',
        };
    }
}