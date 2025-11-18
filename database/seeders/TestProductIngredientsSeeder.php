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
        // All ingredients using base units only (grams, ml, pieces)
        $testIngredients = [
            ['name' => 'Test Wheat Flour', 'unit_type' => 'grams'],
            ['name' => 'Test Fresh Eggs', 'unit_type' => 'pieces'],
            ['name' => 'Test Whole Milk', 'unit_type' => 'ml'],
            ['name' => 'Test Butter', 'unit_type' => 'grams'],
            ['name' => 'Test Sugar', 'unit_type' => 'grams'],
            ['name' => 'Test Olive Oil', 'unit_type' => 'ml'],
            ['name' => 'Test Salt', 'unit_type' => 'grams'],
            ['name' => 'Test Black Pepper', 'unit_type' => 'grams'],
            ['name' => 'Test Chicken Breast', 'unit_type' => 'grams'],
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
                ])
            );

            $createdProducts[$productData['name']] = $product;
        }

        return $createdProducts;
    }

    private function createTestProductIngredients(array $products, array $ingredients): void
    {
        // Define test recipes - all quantities in base units (grams, ml, pieces)
        $testRecipes = [
            'Test Pancake Special' => [
                'Test Wheat Flour' => 250.0, // 250g
                'Test Fresh Eggs' => 2.0, // 2 pieces
                'Test Whole Milk' => 300.0, // 300ml (was 0.3L)
                'Test Butter' => 50.0, // 50g
                'Test Sugar' => 50.0, // 50g (was 0.05kg)
            ],
            'Test Chicken Supreme' => [
                'Test Chicken Breast' => 250.0, // 250g (was 0.25kg)
                'Test Salt' => 2.0, // 2g
                'Test Black Pepper' => 1.0, // 1g
                'Test Olive Oil' => 15.0, // 15ml
            ],
            'Test Coffee Deluxe' => [
                'Test Coffee Beans' => 20.0, // 20g
                'Test Whole Milk' => 200.0, // 200ml (was 0.2L)
                'Test Sugar' => 20.0, // 20g (was 0.02kg)
            ],
            'Test Sweet Treat' => [
                'Test Wheat Flour' => 150.0, // 150g
                'Test Butter' => 75.0, // 75g
                'Test Sugar' => 80.0, // 80g (was 0.08kg)
                'Test Fresh Eggs' => 1.0, // 1 piece
            ],
        ];

        foreach ($testRecipes as $productName => $recipe) {
            if (! isset($products[$productName])) {
                continue;
            }

            $product = $products[$productName];

            // Remove existing ingredients for this test product
            ProductIngredient::query()->where('product_id', $product->id)->delete();

            foreach ($recipe as $ingredientName => $quantity) {
                if (! isset($ingredients[$ingredientName])) {
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
            'grams' => rand(5000, 50000), // 5kg to 50kg worth in grams
            'ml' => rand(1000, 20000), // 1L to 20L worth in ml
            'pieces' => rand(20, 200),
            default => rand(100, 1000),
        };
    }

    private function getMinStock(string $unitType): int
    {
        return match ($unitType) {
            'grams' => 1000, // 1kg worth in grams
            'ml' => 500, // 0.5L worth in ml
            'pieces' => 10,
            default => 50,
        };
    }

    private function getMaxStock(string $unitType): int
    {
        return match ($unitType) {
            'grams' => 50000, // 50kg worth in grams
            'ml' => 25000, // 25L worth in ml
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
            'grams' => 'Dry Storage',
            'ml' => 'Fridge',
            'pieces' => 'Fridge',
            default => 'Main Storage',
        };
    }
}
