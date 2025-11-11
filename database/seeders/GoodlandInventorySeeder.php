<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UnitType;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\IngredientInventory;
use App\Models\Product;
use App\Models\ProductIngredient;
use Illuminate\Database\Seeder;

final class GoodlandInventorySeeder extends Seeder
{
    public function run(): void
    {
        // Create Ingredient Categories for better organization
        $this->createIngredientCategories();

        // Create all ingredients from Goodland Kitchen and Bar inventory
        $ingredients = $this->createGoodlandIngredients();

        // Create Ingredient Inventory for all ingredients
        $this->createIngredientInventory($ingredients);

        // Update existing products to use the proper ingredients
        $this->updateProductIngredients($ingredients);

        // Create beverage products using bar inventory
        $this->createBeverageProducts($ingredients);

        $this->command->info('Goodland Kitchen and Bar inventory seeded successfully!');
    }

    private function createIngredientCategories(): void
    {
        $categories = [
            [
                'name' => 'Kitchen Ingredients',
                'description' => 'Kitchen food ingredients and supplies',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Bar Beverages',
                'description' => 'Bar beverage ingredients and drinks',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Bar Supplies',
                'description' => 'Bar supplies and disposables',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Packaging Supplies',
                'description' => 'Packaging and takeout supplies',
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($categories as $category) {
            Category::query()->firstOrCreate(['name' => $category['name']], $category);
        }
    }

    private function createGoodlandIngredients(): array
    {
        // Goodland Kitchen Inventory
        $kitchenIngredients = [
            ['name' => 'Chicken', 'unit_type' => UnitType::KILOGRAMS->value],
            ['name' => 'Pork', 'unit_type' => UnitType::KILOGRAMS->value],
            ['name' => 'Ground Beef', 'unit_type' => UnitType::KILOGRAMS->value],
            ['name' => 'Ground Pork', 'unit_type' => UnitType::KILOGRAMS->value],
            ['name' => 'Peanut', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Mushroom', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Onion', 'unit_type' => UnitType::KILOGRAMS->value],
            ['name' => 'Red Onion', 'unit_type' => UnitType::KILOGRAMS->value],
            ['name' => 'Potato', 'unit_type' => UnitType::KILOGRAMS->value],
            ['name' => 'Carrots', 'unit_type' => UnitType::KILOGRAMS->value],
            ['name' => 'Sayote', 'unit_type' => UnitType::KILOGRAMS->value],
            ['name' => 'Snowpeas', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Baguio Beans', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Celery', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Cabbage', 'unit_type' => UnitType::KILOGRAMS->value],
            ['name' => 'Zucchini', 'unit_type' => UnitType::PIECES->value],
            ['name' => 'Tofu', 'unit_type' => UnitType::PIECES->value],
            ['name' => 'Eggs', 'unit_type' => UnitType::PIECES->value],
            ['name' => 'Butter', 'unit_type' => UnitType::LITERS->value],
            ['name' => 'Cooking Oil', 'unit_type' => UnitType::LITERS->value],
            ['name' => 'Salt', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Soy Sauce', 'unit_type' => UnitType::LITERS->value],
            ['name' => 'Vinegar', 'unit_type' => UnitType::LITERS->value],
            ['name' => 'Oyster Sauce', 'unit_type' => UnitType::LITERS->value],
            ['name' => 'BBQ Sauce', 'unit_type' => UnitType::LITERS->value],
            ['name' => 'Fish Sauce', 'unit_type' => UnitType::LITERS->value],
            ['name' => 'Sugar', 'unit_type' => UnitType::KILOGRAMS->value],
            ['name' => 'Mayonnaise', 'unit_type' => UnitType::LITERS->value],
            ['name' => 'Mustard', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'All Purpose Cream', 'unit_type' => UnitType::MILLILITERS->value],
            ['name' => 'Parmesan Cheese', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Cheese Bar', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Cheese Sauce', 'unit_type' => UnitType::LITERS->value],
            ['name' => 'Tomato Sauce', 'unit_type' => UnitType::LITERS->value],
            ['name' => 'Donaldo Salt', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Black Pepper', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Garlic Powder', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Dried Parsley', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Dried Basil', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Nacho Chips', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Red Chilis', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Fettuccine Pasta', 'unit_type' => UnitType::GRAMS->value],

            // Goodland Bar Inventory - Beverages and Ingredients
            ['name' => 'Milk', 'unit_type' => UnitType::LITERS->value],
            ['name' => 'Milklab', 'unit_type' => UnitType::LITERS->value],
            ['name' => 'Coffee Beans', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Mixture', 'unit_type' => UnitType::MILLILITERS->value],
            ['name' => 'Condensed Milk', 'unit_type' => UnitType::MILLILITERS->value],
            ['name' => 'Evaporated Milk', 'unit_type' => UnitType::MILLILITERS->value],
            ['name' => 'Biscoff Spread', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Honey', 'unit_type' => UnitType::MILLILITERS->value],
            ['name' => 'Vanilla Syrup', 'unit_type' => UnitType::MILLILITERS->value],
            ['name' => 'Hazelnut Syrup', 'unit_type' => UnitType::MILLILITERS->value],
            ['name' => 'Caramel Syrup', 'unit_type' => UnitType::MILLILITERS->value],
            ['name' => 'Strawberry Syrup', 'unit_type' => UnitType::MILLILITERS->value],
            ['name' => 'Fructose', 'unit_type' => UnitType::MILLILITERS->value],
            ['name' => 'Brown Sugar', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Lemonade', 'unit_type' => UnitType::LITERS->value],
            ['name' => 'Strawberry Mix', 'unit_type' => UnitType::MILLILITERS->value],
            ['name' => 'Blueberry Mix', 'unit_type' => UnitType::MILLILITERS->value],
            ['name' => 'Caramel Sauce', 'unit_type' => UnitType::MILLILITERS->value],
            ['name' => 'Butterscotch Sauce', 'unit_type' => UnitType::MILLILITERS->value],
            ['name' => 'Chocolate Sauce', 'unit_type' => UnitType::MILLILITERS->value],
            ['name' => 'Strawberry Jam', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Blueberry Jam', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Kiwi Jam', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Sinkers', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Colorful Nata', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Coffee Jelly', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Crushed Oreo', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Biscoff Biscuit', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Xuejidong Matcha Powder', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Salted Caramel Powder', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Strawberry Powder', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Frappe Base', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Frosty Whip', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Tea', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Assam Black Tea', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Butterfly Pea', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Hibiscus', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Lemon', 'unit_type' => UnitType::PIECES->value],
            ['name' => 'Calamansi', 'unit_type' => UnitType::PIECES->value],
            ['name' => 'Orange', 'unit_type' => UnitType::PIECES->value],
            ['name' => 'Cinnamon', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Himalayan Salt', 'unit_type' => UnitType::GRAMS->value],

            // Bar Supplies and Packaging
            ['name' => '16oz Cups', 'unit_type' => UnitType::PIECES->value],
            ['name' => '12oz Cups', 'unit_type' => UnitType::PIECES->value],
            ['name' => 'Hot Cups', 'unit_type' => UnitType::PIECES->value],
            ['name' => 'Single Take Out Container', 'unit_type' => UnitType::PIECES->value],
            ['name' => 'Double Take Out Container', 'unit_type' => UnitType::PIECES->value],
            ['name' => 'Thin Straw', 'unit_type' => UnitType::PIECES->value],
            ['name' => 'Thick Straw', 'unit_type' => UnitType::PIECES->value],
            ['name' => 'Cup Sleeves', 'unit_type' => UnitType::PIECES->value],
            ['name' => 'Hot Lid', 'unit_type' => UnitType::PIECES->value],
            ['name' => 'Strawless Lid', 'unit_type' => UnitType::PIECES->value],
            ['name' => 'Dome Lid', 'unit_type' => UnitType::PIECES->value],
            ['name' => 'Whip Cream Charger', 'unit_type' => UnitType::PIECES->value],
            ['name' => 'Thermal Paper', 'unit_type' => UnitType::PIECES->value],
            ['name' => 'Sugar Packets', 'unit_type' => UnitType::PIECES->value],
            ['name' => 'Creamer Packets', 'unit_type' => UnitType::PIECES->value],
        ];

        $createdIngredients = [];
        foreach ($kitchenIngredients as $ingredient) {
            $createdIngredients[$ingredient['name']] = Ingredient::query()->firstOrCreate(
                ['name' => $ingredient['name']],
                $ingredient
            );
        }

        return $createdIngredients;
    }

    private function createIngredientInventory(array $ingredients): void
    {
        foreach ($ingredients as $ingredient) {
            IngredientInventory::query()->firstOrCreate([
                'ingredient_id' => $ingredient->id,
            ], [
                'current_stock' => $this->getRandomStock($ingredient->unit_type),
                'min_stock_level' => $this->getMinStockLevel($ingredient->unit_type),
                'max_stock_level' => $this->getMaxStockLevel($ingredient->unit_type),
                'unit_cost' => $this->getUnitCost($ingredient->name),
                'location' => $this->getIngredientLocation($ingredient->name),
                'supplier_info' => 'Goodland Supplier',
                'last_restocked_at' => now()->subDays(rand(1, 30)),
            ]);
        }
    }

    private function updateProductIngredients(array $ingredients): void
    {
        // Update existing products to use the correct ingredient names
        $this->updateExistingProducts($ingredients);
    }

    private function updateExistingProducts(array $ingredients): void
    {
        // Get all existing products and update their ingredient mappings
        $products = Product::all();

        foreach ($products as $product) {
            $this->updateProductRecipe($product, $ingredients);
        }
    }

    private function updateProductRecipe(Product $product, array $ingredients): void
    {
        // Remove existing ingredient relationships
        $product->ingredients()->delete();

        // Add new ingredient relationships based on product name
        $recipe = $this->getRecipeForProduct($product->name, $ingredients);

        foreach ($recipe as $ingredientId => $quantity) {
            if ($ingredientId > 0) {
                ProductIngredient::query()->create([
                    'product_id' => $product->id,
                    'ingredient_id' => $ingredientId,
                    'quantity_required' => $quantity,
                ]);
            }
        }
    }

    private function getRecipeForProduct(string $productName, array $ingredients): array
    {
        $recipes = [
            // Pancit Recipes
            'Pancit Bihon (SOLO)' => [
                $this->getIngredientId($ingredients, 'Onion') => 0.030,
                $this->getIngredientId($ingredients, 'Carrots') => 0.050,
                $this->getIngredientId($ingredients, 'Cabbage') => 0.100,
                $this->getIngredientId($ingredients, 'Soy Sauce') => 0.030,
                $this->getIngredientId($ingredients, 'Cooking Oil') => 0.020,
            ],
            'Pancit Canton (SOLO)' => [
                $this->getIngredientId($ingredients, 'Onion') => 0.030,
                $this->getIngredientId($ingredients, 'Carrots') => 0.050,
                $this->getIngredientId($ingredients, 'Cabbage') => 0.100,
                $this->getIngredientId($ingredients, 'Soy Sauce') => 0.030,
                $this->getIngredientId($ingredients, 'Cooking Oil') => 0.020,
            ],
            'Beef Spaghetti' => [
                $this->getIngredientId($ingredients, 'Ground Beef') => 0.150,
                $this->getIngredientId($ingredients, 'Tomato Sauce') => 0.150,
                $this->getIngredientId($ingredients, 'Onion') => 0.030,
                $this->getIngredientId($ingredients, 'Garlic Powder') => 0.005,
                $this->getIngredientId($ingredients, 'Cheese Bar') => 0.050,
            ],
            'Beef Stroganoff' => [
                $this->getIngredientId($ingredients, 'Ground Beef') => 0.200,
                $this->getIngredientId($ingredients, 'Mushroom') => 0.100,
                $this->getIngredientId($ingredients, 'All Purpose Cream') => 100,
                $this->getIngredientId($ingredients, 'Onion') => 0.030,
            ],
            'Carbonara' => [
                $this->getIngredientId($ingredients, 'Fettuccine Pasta') => 200,
                $this->getIngredientId($ingredients, 'All Purpose Cream') => 150,
                $this->getIngredientId($ingredients, 'Eggs') => 2,
                $this->getIngredientId($ingredients, 'Parmesan Cheese') => 30,
                $this->getIngredientId($ingredients, 'Black Pepper') => 2,
            ],
            'Chicken Tenders' => [
                $this->getIngredientId($ingredients, 'Chicken') => 0.300,
                $this->getIngredientId($ingredients, 'Cooking Oil') => 0.100,
                $this->getIngredientId($ingredients, 'Salt') => 2,
                $this->getIngredientId($ingredients, 'Black Pepper') => 1,
            ],
            'Cheesy Spam Omelette' => [
                $this->getIngredientId($ingredients, 'Eggs') => 3,
                $this->getIngredientId($ingredients, 'Cheese Bar') => 0.040,
                $this->getIngredientId($ingredients, 'Cooking Oil') => 0.020,
                $this->getIngredientId($ingredients, 'Salt') => 2,
                $this->getIngredientId($ingredients, 'Black Pepper') => 1,
            ],
            'Chicken Omelette' => [
                $this->getIngredientId($ingredients, 'Eggs') => 3,
                $this->getIngredientId($ingredients, 'Chicken') => 0.100,
                $this->getIngredientId($ingredients, 'Onion') => 0.020,
                $this->getIngredientId($ingredients, 'Cooking Oil') => 0.020,
            ],
            'Beefy Cheese Burger' => [
                $this->getIngredientId($ingredients, 'Ground Beef') => 0.200,
                $this->getIngredientId($ingredients, 'Cheese Bar') => 0.050,
                $this->getIngredientId($ingredients, 'Onion') => 0.020,
                $this->getIngredientId($ingredients, 'Tomato Sauce') => 0.020,
                $this->getIngredientId($ingredients, 'Mayonnaise') => 0.020,
            ],
            'Zucchini Beef Burger' => [
                $this->getIngredientId($ingredients, 'Ground Beef') => 0.150,
                $this->getIngredientId($ingredients, 'Zucchini') => 1,
                $this->getIngredientId($ingredients, 'Cheese Bar') => 0.040,
                $this->getIngredientId($ingredients, 'Onion') => 0.020,
            ],
        ];

        return $recipes[$productName] ?? [];
    }

    private function createBeverageProducts(array $ingredients): void
    {
        $beverageCategory = Category::where('name', 'Bar Beverages')->first();
        if (! $beverageCategory) {
            return;
        }

        $beverages = [
            'Classic Coffee' => 85,
            'Hazelnut Latte' => 95,
            'Caramel Macchiato' => 95,
            'Vanilla Latte' => 95,
            'Matcha Latte' => 105,
            'Strawberry Frappe' => 115,
            'Blueberry Frappe' => 115,
            'Chocolate Frappe' => 110,
            'Iced Tea' => 65,
            'Lemonade' => 70,
            'Fruit Tea' => 75,
        ];

        foreach ($beverages as $name => $price) {
            $product = Product::query()->firstOrCreate([
                'name' => $name,
            ], [
                'category_id' => $beverageCategory->id,
                'price' => $price,
                'description' => 'Refreshing beverage',
                'preparation_time' => 5,
                'is_active' => true,
            ]);

            $this->addBeverageRecipe($product, $ingredients);
        }
    }

    private function addBeverageRecipe(Product $product, array $ingredients): void
    {
        $recipes = [
            'Classic Coffee' => [
                $this->getIngredientId($ingredients, 'Coffee Beans') => 20,
                $this->getIngredientId($ingredients, 'Hot Cups') => 1,
                $this->getIngredientId($ingredients, 'Hot Lid') => 1,
            ],
            'Hazelnut Latte' => [
                $this->getIngredientId($ingredients, 'Coffee Beans') => 20,
                $this->getIngredientId($ingredients, 'Hazelnut Syrup') => 30,
                $this->getIngredientId($ingredients, 'Milk') => 150,
                $this->getIngredientId($ingredients, 'Hot Cups') => 1,
                $this->getIngredientId($ingredients, 'Hot Lid') => 1,
            ],
            'Caramel Macchiato' => [
                $this->getIngredientId($ingredients, 'Coffee Beans') => 20,
                $this->getIngredientId($ingredients, 'Caramel Syrup') => 30,
                $this->getIngredientId($ingredients, 'Milk') => 150,
                $this->getIngredientId($ingredients, 'Caramel Sauce') => 10,
                $this->getIngredientId($ingredients, 'Hot Cups') => 1,
            ],
            'Vanilla Latte' => [
                $this->getIngredientId($ingredients, 'Coffee Beans') => 20,
                $this->getIngredientId($ingredients, 'Vanilla Syrup') => 30,
                $this->getIngredientId($ingredients, 'Milk') => 150,
                $this->getIngredientId($ingredients, 'Hot Cups') => 1,
                $this->getIngredientId($ingredients, 'Hot Lid') => 1,
            ],
            'Matcha Latte' => [
                $this->getIngredientId($ingredients, 'Xuejidong Matcha Powder') => 5,
                $this->getIngredientId($ingredients, 'Milk') => 200,
                $this->getIngredientId($ingredients, 'Hot Cups') => 1,
                $this->getIngredientId($ingredients, 'Hot Lid') => 1,
            ],
            'Strawberry Frappe' => [
                $this->getIngredientId($ingredients, 'Strawberry Mix') => 50,
                $this->getIngredientId($ingredients, 'Frappe Base') => 15,
                $this->getIngredientId($ingredients, 'Milk') => 100,
                $this->getIngredientId($ingredients, 'Crushed Oreo') => 10,
                $this->getIngredientId($ingredients, '16oz Cups') => 1,
                $this->getIngredientId($ingredients, 'Strawless Lid') => 1,
                $this->getIngredientId($ingredients, 'Thick Straw') => 1,
            ],
            'Blueberry Frappe' => [
                $this->getIngredientId($ingredients, 'Blueberry Mix') => 50,
                $this->getIngredientId($ingredients, 'Frappe Base') => 15,
                $this->getIngredientId($ingredients, 'Milk') => 100,
                $this->getIngredientId($ingredients, '16oz Cups') => 1,
                $this->getIngredientId($ingredients, 'Strawless Lid') => 1,
                $this->getIngredientId($ingredients, 'Thick Straw') => 1,
            ],
            'Chocolate Frappe' => [
                $this->getIngredientId($ingredients, 'Chocolate Sauce') => 30,
                $this->getIngredientId($ingredients, 'Frappe Base') => 15,
                $this->getIngredientId($ingredients, 'Milk') => 100,
                $this->getIngredientId($ingredients, 'Crushed Oreo') => 15,
                $this->getIngredientId($ingredients, '16oz Cups') => 1,
                $this->getIngredientId($ingredients, 'Strawless Lid') => 1,
                $this->getIngredientId($ingredients, 'Thick Straw') => 1,
            ],
            'Iced Tea' => [
                $this->getIngredientId($ingredients, 'Tea') => 10,
                $this->getIngredientId($ingredients, 'Brown Sugar') => 20,
                $this->getIngredientId($ingredients, 'Lemon') => 0.5,
                $this->getIngredientId($ingredients, '16oz Cups') => 1,
                $this->getIngredientId($ingredients, 'Strawless Lid') => 1,
                $this->getIngredientId($ingredients, 'Thin Straw') => 1,
            ],
            'Lemonade' => [
                $this->getIngredientId($ingredients, 'Lemonade') => 300,
                $this->getIngredientId($ingredients, 'Lemon') => 1,
                $this->getIngredientId($ingredients, 'Honey') => 20,
                $this->getIngredientId($ingredients, '16oz Cups') => 1,
                $this->getIngredientId($ingredients, 'Strawless Lid') => 1,
                $this->getIngredientId($ingredients, 'Thin Straw') => 1,
            ],
            'Fruit Tea' => [
                $this->getIngredientId($ingredients, 'Assam Black Tea') => 10,
                $this->getIngredientId($ingredients, 'Strawberry Mix') => 20,
                $this->getIngredientId($ingredients, 'Blueberry Mix') => 20,
                $this->getIngredientId($ingredients, 'Butterfly Pea') => 5,
                $this->getIngredientId($ingredients, '16oz Cups') => 1,
                $this->getIngredientId($ingredients, 'Strawless Lid') => 1,
                $this->getIngredientId($ingredients, 'Thin Straw') => 1,
            ],
        ];

        $recipe = $recipes[$product->name] ?? [];

        foreach ($recipe as $ingredientId => $quantity) {
            if ($ingredientId > 0) {
                ProductIngredient::query()->firstOrCreate([
                    'product_id' => $product->id,
                    'ingredient_id' => $ingredientId,
                ], [
                    'quantity_required' => $quantity,
                ]);
            }
        }
    }

    private function getRandomStock(UnitType $unitType): int
    {
        return match ($unitType->value) {
            'grams' => rand(500, 10000),
            'kilograms' => rand(2, 50),
            'ml' => rand(500, 5000),
            'liters' => rand(1, 20),
            'pieces' => rand(50, 500),
            default => rand(100, 1000),
        };
    }

    private function getMinStockLevel(UnitType $unitType): int
    {
        return match ($unitType->value) {
            'grams' => 500,
            'kilograms' => 1,
            'ml' => 200,
            'liters' => 1,
            'pieces' => 20,
            default => 50,
        };
    }

    private function getMaxStockLevel(UnitType $unitType): int
    {
        return match ($unitType->value) {
            'grams' => 10000,
            'kilograms' => 100,
            'ml' => 10000,
            'liters' => 50,
            'pieces' => 1000,
            default => 1000,
        };
    }

    private function getUnitCost(string $ingredientName): float
    {
        $costs = [
            // Kitchen ingredients
            'Chicken' => 0.015,
            'Pork' => 0.018,
            'Ground Beef' => 0.020,
            'Ground Pork' => 0.017,
            'Eggs' => 0.012,
            'Onion' => 0.003,
            'Red Onion' => 0.004,
            'Potato' => 0.002,
            'Carrots' => 0.004,
            'Mushroom' => 0.008,
            'Cabbage' => 0.002,
            'Zucchini' => 0.015,
            'Tofu' => 0.010,
            'Butter' => 0.008,
            'Cooking Oil' => 0.004,
            'Soy Sauce' => 0.003,
            'Vinegar' => 0.002,
            'Oyster Sauce' => 0.008,
            'BBQ Sauce' => 0.006,
            'Fish Sauce' => 0.004,
            'Sugar' => 0.0015,
            'Mayonnaise' => 0.005,
            'All Purpose Cream' => 0.006,
            'Parmesan Cheese' => 0.015,
            'Cheese Bar' => 0.012,
            'Cheese Sauce' => 0.008,
            'Tomato Sauce' => 0.004,
            'Black Pepper' => 0.010,
            'Garlic Powder' => 0.012,
            'Fettuccine Pasta' => 0.003,

            // Bar beverages
            'Milk' => 0.002,
            'Milklab' => 0.004,
            'Coffee Beans' => 0.050,
            'Biscoff Spread' => 0.015,
            'Honey' => 0.008,
            'Vanilla Syrup' => 0.006,
            'Hazelnut Syrup' => 0.006,
            'Caramel Syrup' => 0.006,
            'Strawberry Syrup' => 0.006,
            'Caramel Sauce' => 0.008,
            'Chocolate Sauce' => 0.008,
            'Xuejidong Matcha Powder' => 0.200,
            'Frappe Base' => 0.020,
            'Crushed Oreo' => 0.010,
            'Tea' => 0.020,
            'Assam Black Tea' => 0.025,
            'Butterfly Pea' => 0.030,
            'Lemon' => 0.015,
            'Calamansi' => 0.008,
            'Orange' => 0.020,

            // Supplies
            '16oz Cups' => 0.025,
            '12oz Cups' => 0.020,
            'Hot Cups' => 0.018,
            'Hot Lid' => 0.008,
            'Strawless Lid' => 0.010,
            'Dome Lid' => 0.012,
            'Thin Straw' => 0.005,
            'Thick Straw' => 0.008,
        ];

        return $costs[$ingredientName] ?? 0.005;
    }

    private function getIngredientLocation(string $ingredientName): string
    {
        $locations = [
            'Chicken' => 'Freezer',
            'Pork' => 'Freezer',
            'Ground Beef' => 'Freezer',
            'Ground Pork' => 'Freezer',
            'Milk' => 'Fridge',
            'Milklab' => 'Fridge',
            'Eggs' => 'Fridge',
            'Butter' => 'Fridge',
            'All Purpose Cream' => 'Fridge',
            'Cheese Bar' => 'Fridge',
            'Parmesan Cheese' => 'Fridge',
            'Cheese Sauce' => 'Fridge',
            'Condensed Milk' => 'Pantry',
            'Evaporated Milk' => 'Pantry',
            'Biscoff Spread' => 'Pantry',
            'Fridge' => 'Pantry',
            '16oz Cups' => 'Supply Room',
            '12oz Cups' => 'Supply Room',
            'Hot Cups' => 'Supply Room',
            'Hot Lid' => 'Supply Room',
            'Strawless Lid' => 'Supply Room',
            'Dome Lid' => 'Supply Room',
            'Thin Straw' => 'Supply Room',
            'Thick Straw' => 'Supply Room',
            'Cup Sleeves' => 'Supply Room',
            'Single Take Out Container' => 'Supply Room',
            'Double Take Out Container' => 'Supply Room',
            'Thermal Paper' => 'Supply Room',
            'Sugar Packets' => 'Supply Room',
            'Creamer Packets' => 'Supply Room',
            'Whip Cream Charger' => 'Supply Room',
        ];

        return $locations[$ingredientName] ?? 'Main Storage';
    }

    private function getIngredientId(array $ingredients, string $ingredientName): int
    {
        if (! isset($ingredients[$ingredientName])) {
            $this->command->warn("Warning: Ingredient '{$ingredientName}' not found");

            return 0;
        }

        $ingredient = $ingredients[$ingredientName];
        if (! $ingredient || ! $ingredient->id) {
            $this->command->warn("Warning: Ingredient '{$ingredientName}' has no valid ID");

            return 0;
        }

        return $ingredient->id;
    }
}
