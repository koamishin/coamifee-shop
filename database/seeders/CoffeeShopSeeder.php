<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\IngredientInventory;
use App\Models\Product;
use App\Models\ProductIngredient;
use Illuminate\Database\Seeder;

final class CoffeeShopSeeder extends Seeder
{
    public function run(): void
    {
        // Create Categories with proper structure
        $categories = [
            ['name' => 'Coffee', 'description' => 'Hot and cold coffee beverages', 'is_active' => true, 'sort_order' => 1],
            ['name' => 'Tea', 'description' => 'Various tea selections', 'is_active' => true, 'sort_order' => 2],
            ['name' => 'Food', 'description' => 'Pastries, sandwiches, and meals', 'is_active' => true, 'sort_order' => 3],
            ['name' => 'Desserts', 'description' => 'Sweet treats and desserts', 'is_active' => true, 'sort_order' => 4],
        ];

        $createdCategories = [];
        foreach ($categories as $category) {
            $createdCategories[$category['name']] = Category::create($category);
        }

        $coffeeCategory = $createdCategories['Coffee'];
        $teaCategory = $createdCategories['Tea'];
        $foodCategory = $createdCategories['Food'];
        $dessertCategory = $createdCategories['Desserts'];

        // Create Ingredients
        $ingredients = [
            // Trackable Ingredients
            ['name' => 'Coffee Beans (Espresso)', 'unit_type' => 'grams', 'is_trackable' => true, 'unit_cost' => 0.02, 'current_stock' => 5000],
            ['name' => 'Whole Milk', 'unit_type' => 'ml', 'is_trackable' => true, 'unit_cost' => 0.001, 'current_stock' => 10000],
            ['name' => 'Oat Milk', 'unit_type' => 'ml', 'is_trackable' => true, 'unit_cost' => 0.002, 'current_stock' => 5000],
            ['name' => 'Vanilla Syrup', 'unit_type' => 'ml', 'is_trackable' => true, 'unit_cost' => 0.005, 'current_stock' => 2000],
            ['name' => 'Caramel Syrup', 'unit_type' => 'ml', 'is_trackable' => true, 'unit_cost' => 0.005, 'current_stock' => 2000],
            ['name' => 'Chocolate Sauce', 'unit_type' => 'ml', 'is_trackable' => true, 'unit_cost' => 0.008, 'current_stock' => 1500],
            ['name' => 'Whipped Cream', 'unit_type' => 'grams', 'is_trackable' => true, 'unit_cost' => 0.01, 'current_stock' => 2000],
            ['name' => 'Ice Cubes', 'unit_type' => 'pieces', 'is_trackable' => true, 'unit_cost' => 0.001, 'current_stock' => 5000],

            // Untrackable Ingredients
            ['name' => 'Beef Patty', 'unit_type' => 'grams', 'is_trackable' => false, 'unit_cost' => 0.015, 'current_stock' => 0],
            ['name' => 'Chicken Breast', 'unit_type' => 'grams', 'is_trackable' => false, 'unit_cost' => 0.012, 'current_stock' => 0],
            ['name' => 'Bacon', 'unit_type' => 'grams', 'is_trackable' => false, 'unit_cost' => 0.025, 'current_stock' => 0],
            ['name' => 'Lettuce', 'unit_type' => 'grams', 'is_trackable' => false, 'unit_cost' => 0.003, 'current_stock' => 0],
            ['name' => 'Tomato', 'unit_type' => 'grams', 'is_trackable' => false, 'unit_cost' => 0.004, 'current_stock' => 0],
            ['name' => 'Cheese Slice', 'unit_type' => 'pieces', 'is_trackable' => false, 'unit_cost' => 0.05, 'current_stock' => 0],
        ];

        $createdIngredients = [];
        foreach ($ingredients as $ingredient) {
            $createdIngredients[$ingredient['name']] = Ingredient::create($ingredient);
        }

        // Create Ingredient Inventory for trackable ingredients
        foreach ($createdIngredients as $ingredient) {
            if ($ingredient->is_trackable) {
                IngredientInventory::create([
                    'ingredient_id' => $ingredient->id,
                    'current_stock' => $ingredient->current_stock,
                    'min_stock_level' => $ingredient->unit_type === 'grams' ? 500 : 1000,
                    'max_stock_level' => $ingredient->unit_type === 'grams' ? 10000 : 20000,
                    'location' => 'Main Storage',
                    'last_restocked_at' => now(),
                ]);
            }
        }

        // Create Products
        $products = [
            // Coffee Products
            ['name' => 'Espresso', 'price' => 2.50, 'category_id' => $coffeeCategory->id, 'preparation_time' => 2],
            ['name' => 'Cappuccino', 'price' => 3.50, 'category_id' => $coffeeCategory->id, 'preparation_time' => 4],
            ['name' => 'Latte', 'price' => 4.00, 'category_id' => $coffeeCategory->id, 'preparation_time' => 5],
            ['name' => 'Vanilla Latte', 'price' => 4.50, 'category_id' => $coffeeCategory->id, 'preparation_time' => 5],
            ['name' => 'Caramel Macchiato', 'price' => 5.00, 'category_id' => $coffeeCategory->id, 'preparation_time' => 6],
            ['name' => 'Mocha', 'price' => 4.50, 'category_id' => $coffeeCategory->id, 'preparation_time' => 6],
            ['name' => 'Iced Coffee', 'price' => 3.00, 'category_id' => $coffeeCategory->id, 'preparation_time' => 3],

            // Tea Products
            ['name' => 'Green Tea', 'price' => 2.00, 'category_id' => $teaCategory->id, 'preparation_time' => 3],
            ['name' => 'Earl Grey Tea', 'price' => 2.00, 'category_id' => $teaCategory->id, 'preparation_time' => 3],
            ['name' => 'Iced Tea', 'price' => 2.50, 'category_id' => $teaCategory->id, 'preparation_time' => 3],
            ['name' => 'Chamomile Tea', 'price' => 2.25, 'category_id' => $teaCategory->id, 'preparation_time' => 3],

            // Food Products
            ['name' => 'Beef Burger', 'price' => 8.50, 'category_id' => $foodCategory->id, 'preparation_time' => 12],
            ['name' => 'Chicken Sandwich', 'price' => 7.50, 'category_id' => $foodCategory->id, 'preparation_time' => 10],
            ['name' => 'Bacon Burger', 'price' => 9.00, 'category_id' => $foodCategory->id, 'preparation_time' => 13],
            ['name' => 'Caesar Salad', 'price' => 6.50, 'category_id' => $foodCategory->id, 'preparation_time' => 8],
            ['name' => 'Grilled Cheese Sandwich', 'price' => 5.50, 'category_id' => $foodCategory->id, 'preparation_time' => 7],

            // Dessert Products
            ['name' => 'Chocolate Cake', 'price' => 4.50, 'category_id' => $dessertCategory->id, 'preparation_time' => 2],
            ['name' => 'Cheesecake', 'price' => 5.00, 'category_id' => $dessertCategory->id, 'preparation_time' => 2],
            ['name' => 'Apple Pie', 'price' => 4.00, 'category_id' => $dessertCategory->id, 'preparation_time' => 2],
            ['name' => 'Ice Cream Sundae', 'price' => 3.50, 'category_id' => $dessertCategory->id, 'preparation_time' => 3],
        ];

        $createdProducts = [];
        foreach ($products as $product) {
            $createdProducts[$product['name']] = Product::create($product);
        }

        // Create Product Ingredients (Recipes)
        $recipes = [
            // Coffee Recipes
            'Espresso' => [
                ['ingredient_id' => $createdIngredients['Coffee Beans (Espresso)']->id, 'quantity_required' => 18],
            ],
            'Cappuccino' => [
                ['ingredient_id' => $createdIngredients['Coffee Beans (Espresso)']->id, 'quantity_required' => 18],
                ['ingredient_id' => $createdIngredients['Whole Milk']->id, 'quantity_required' => 150],
                ['ingredient_id' => $createdIngredients['Whipped Cream']->id, 'quantity_required' => 10],
            ],
            'Latte' => [
                ['ingredient_id' => $createdIngredients['Coffee Beans (Espresso)']->id, 'quantity_required' => 18],
                ['ingredient_id' => $createdIngredients['Whole Milk']->id, 'quantity_required' => 200],
            ],
            'Vanilla Latte' => [
                ['ingredient_id' => $createdIngredients['Coffee Beans (Espresso)']->id, 'quantity_required' => 18],
                ['ingredient_id' => $createdIngredients['Whole Milk']->id, 'quantity_required' => 200],
                ['ingredient_id' => $createdIngredients['Vanilla Syrup']->id, 'quantity_required' => 15],
            ],
            'Caramel Macchiato' => [
                ['ingredient_id' => $createdIngredients['Coffee Beans (Espresso)']->id, 'quantity_required' => 18],
                ['ingredient_id' => $createdIngredients['Whole Milk']->id, 'quantity_required' => 180],
                ['ingredient_id' => $createdIngredients['Vanilla Syrup']->id, 'quantity_required' => 10],
                ['ingredient_id' => $createdIngredients['Caramel Syrup']->id, 'quantity_required' => 15],
                ['ingredient_id' => $createdIngredients['Whipped Cream']->id, 'quantity_required' => 15],
            ],
            'Mocha' => [
                ['ingredient_id' => $createdIngredients['Coffee Beans (Espresso)']->id, 'quantity_required' => 18],
                ['ingredient_id' => $createdIngredients['Whole Milk']->id, 'quantity_required' => 200],
                ['ingredient_id' => $createdIngredients['Chocolate Sauce']->id, 'quantity_required' => 20],
                ['ingredient_id' => $createdIngredients['Whipped Cream']->id, 'quantity_required' => 10],
            ],
            'Iced Coffee' => [
                ['ingredient_id' => $createdIngredients['Coffee Beans (Espresso)']->id, 'quantity_required' => 18],
                ['ingredient_id' => $createdIngredients['Ice Cubes']->id, 'quantity_required' => 6],
                ['ingredient_id' => $createdIngredients['Whole Milk']->id, 'quantity_required' => 100],
            ],

            // Food Recipes
            'Beef Burger' => [
                ['ingredient_id' => $createdIngredients['Beef Patty']->id, 'quantity_required' => 200],
                ['ingredient_id' => $createdIngredients['Lettuce']->id, 'quantity_required' => 30],
                ['ingredient_id' => $createdIngredients['Tomato']->id, 'quantity_required' => 25],
                ['ingredient_id' => $createdIngredients['Cheese Slice']->id, 'quantity_required' => 1],
            ],
            'Chicken Sandwich' => [
                ['ingredient_id' => $createdIngredients['Chicken Breast']->id, 'quantity_required' => 150],
                ['ingredient_id' => $createdIngredients['Lettuce']->id, 'quantity_required' => 20],
                ['ingredient_id' => $createdIngredients['Tomato']->id, 'quantity_required' => 15],
            ],
            'Bacon Burger' => [
                ['ingredient_id' => $createdIngredients['Beef Patty']->id, 'quantity_required' => 200],
                ['ingredient_id' => $createdIngredients['Bacon']->id, 'quantity_required' => 50],
                ['ingredient_id' => $createdIngredients['Lettuce']->id, 'quantity_required' => 30],
                ['ingredient_id' => $createdIngredients['Tomato']->id, 'quantity_required' => 25],
                ['ingredient_id' => $createdIngredients['Cheese Slice']->id, 'quantity_required' => 1],
            ],
        ];

        foreach ($recipes as $productName => $ingredients) {
            $product = $createdProducts[$productName];
            foreach ($ingredients as $ingredient) {
                ProductIngredient::create([
                    'product_id' => $product->id,
                    'ingredient_id' => $ingredient['ingredient_id'],
                    'quantity_required' => $ingredient['quantity_required'],
                ]);
            }
        }

        $this->command->info('Coffee shop data seeded successfully!');
    }
}
