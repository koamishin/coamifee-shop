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

final class CoffeeShopSeeder extends Seeder
{
    public function run(): void
    {
        // Create Categories with comprehensive structure
        $categories = [
            [
                'name' => 'Pancit',
                'icon' => 'heroicon-o-bowl-food',
                'description' => 'Filipino noodle dishes for solo and group servings',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Pasta',
                'icon' => 'heroicon-o-pizza',
                'description' => 'Classic pasta dishes with various sauces',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Desserts',
                'icon' => 'heroicon-o-cake',
                'description' => 'Sweet treats and baked goods',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Pica-Pica',
                'icon' => 'heroicon-o-sparkles',
                'description' => 'Finger foods and appetizers',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Salad',
                'icon' => 'heroicon-o-leaf',
                'description' => 'Fresh and healthy salad options',
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'Fries',
                'icon' => 'heroicon-o-fire',
                'description' => 'Crispy fried potato and sweet potato varieties',
                'is_active' => true,
                'sort_order' => 6,
            ],
            [
                'name' => 'Burger',
                'icon' => 'heroicon-o-square-3-stack-3d',
                'description' => 'Gourmet beef and specialty burgers',
                'is_active' => true,
                'sort_order' => 7,
            ],
            [
                'name' => 'Sandwiches',
                'icon' => 'heroicon-o-bars-3-bottom-left',
                'description' => 'Classic and specialty sandwich varieties',
                'is_active' => true,
                'sort_order' => 8,
            ],
            [
                'name' => 'Omelette',
                'icon' => 'heroicon-o-egg',
                'description' => 'Fluffy omelettes with various fillings',
                'is_active' => true,
                'sort_order' => 9,
            ],
            [
                'name' => 'SILOG Meals',
                'icon' => 'heroicon-o-sun',
                'description' => 'Filipino breakfast combinations with garlic rice',
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'name' => 'Hot Plates',
                'icon' => 'heroicon-o-flame',
                'description' => 'Hearty breakfast plates',
                'is_active' => true,
                'sort_order' => 11,
            ],
            [
                'name' => 'Rice Meals - Beef',
                'icon' => 'heroicon-o-cow',
                'description' => 'Beef dishes served with rice',
                'is_active' => true,
                'sort_order' => 12,
            ],
            [
                'name' => 'Rice Meals - Chicken',
                'icon' => 'heroicon-o-bird',
                'description' => 'Chicken dishes served with rice',
                'is_active' => true,
                'sort_order' => 13,
            ],
            [
                'name' => 'Rice Meals - Others',
                'icon' => 'heroicon-o-star',
                'description' => 'Specialty rice meals',
                'is_active' => true,
                'sort_order' => 14,
            ],
            [
                'name' => 'Add-ons',
                'icon' => 'heroicon-o-plus',
                'description' => 'Additional items and sides',
                'is_active' => true,
                'sort_order' => 15,
            ],
        ];

        $createdCategories = [];
        foreach ($categories as $category) {
            $createdCategories[$category['name']] = Category::query()->firstOrCreate(['name' => $category['name']], $category);
        }

        // Create comprehensive Ingredients
        $ingredients = [
            // Noodles & Pasta Ingredients
            ['name' => 'Bihon Noodles', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Canton Noodles', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Sotanghon Noodles', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Miki Noodles', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Pancit Noodles Mix', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Spaghetti Pasta', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Fettuccine Pasta', 'unit_type' => UnitType::GRAMS->value],

            // Meat & Protein
            ['name' => 'Beef Ground', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Beef Strips', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Beef Brisket', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Chicken Breast', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Chicken Thighs', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Chicken Tenders', 'unit_type' => UnitType::PIECES->value],
            ['name' => 'Chicken Nuggets', 'unit_type' => UnitType::PIECES->value],
            ['name' => 'Pork Liempo', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Porkchop', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Pork Satay', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Longganisa', 'unit_type' => UnitType::PIECES->value],
            ['name' => 'Tapa', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Corned Beef', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Bacon', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Ham', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Tempura Shrimp', 'unit_type' => UnitType::PIECES->value],
            ['name' => 'Dilis', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Pusit', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Tuyo', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Boneless Bangus', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Crab Meat', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Tuna Flakes', 'unit_type' => UnitType::GRAMS->value],

            // Vegetables
            ['name' => 'Cabbage', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Carrots', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Bell Peppers', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Onions', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Garlic', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Ginger', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Mushrooms', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Broccoli', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Lettuce', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Tomatoes', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Potatoes', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Sweet Potato', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Green Beans', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Spinach', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Celery', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Pandan Leaves', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Basil', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Chili', 'unit_type' => UnitType::GRAMS->value],

            // Fruits
            ['name' => 'Banana', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Apples', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Grapes', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Walnuts', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Matcha Powder', 'unit_type' => UnitType::GRAMS->value],

            // Dairy & Eggs
            ['name' => 'Eggs', 'unit_type' => UnitType::PIECES->value],
            ['name' => 'Cheese', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Cream Cheese', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Butter', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Milk', 'unit_type' => UnitType::MILLILITERS->value],
            ['name' => 'Heavy Cream', 'unit_type' => UnitType::MILLILITERS->value],
            ['name' => 'Sour Cream', 'unit_type' => UnitType::MILLILITERS->value],

            // Baking Ingredients
            ['name' => 'Flour', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Sugar', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Brown Sugar', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Cocoa Powder', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Chocolate Chips', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Baking Powder', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Baking Soda', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Vanilla Extract', 'unit_type' => UnitType::MILLILITERS->value],
            ['name' => 'Oil', 'unit_type' => UnitType::MILLILITERS->value],

            // Sauces & Condiments
            ['name' => 'Soy Sauce', 'unit_type' => UnitType::MILLILITERS->value],
            ['name' => 'Oyster Sauce', 'unit_type' => UnitType::MILLILITERS->value],
            ['name' => 'Fish Sauce', 'unit_type' => UnitType::MILLILITERS->value],
            ['name' => 'Vinegar', 'unit_type' => UnitType::MILLILITERS->value],
            ['name' => 'Ketchup', 'unit_type' => UnitType::MILLILITERS->value],
            ['name' => 'Mayonnaise', 'unit_type' => UnitType::MILLILITERS->value],
            ['name' => 'Tomato Sauce', 'unit_type' => UnitType::MILLILITERS->value],
            ['name' => 'Pancit Sauce', 'unit_type' => UnitType::MILLILITERS->value],
            ['name' => 'Curry Powder', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Coconut Milk', 'unit_type' => UnitType::MILLILITERS->value],

            // Rice & Grains
            ['name' => 'Rice', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Garlic Rice', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Fried Rice Mix', 'unit_type' => UnitType::GRAMS->value],

            // Spices & Seasonings
            ['name' => 'Salt', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Pepper', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Sugar', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Patis', 'unit_type' => UnitType::MILLILITERS->value],

            // Additional missing ingredients
            ['name' => 'Buns', 'unit_type' => UnitType::PIECES->value],
            ['name' => 'Bread', 'unit_type' => UnitType::PIECES->value],
            ['name' => 'Cucumber', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Peanut Sauce', 'unit_type' => UnitType::MILLILITERS->value],
            ['name' => 'Pineapple', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Sauce', 'unit_type' => UnitType::MILLILITERS->value],
            ['name' => 'Spam', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Turmeric', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Vegetables', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Zucchini', 'unit_type' => UnitType::GRAMS->value],
            ['name' => 'Honey', 'unit_type' => UnitType::MILLILITERS->value],
            ['name' => 'Cream', 'unit_type' => UnitType::MILLILITERS->value],
        ];

        $createdIngredients = [];
        foreach ($ingredients as $ingredient) {
            $createdIngredients[$ingredient['name']] = Ingredient::query()->firstOrCreate(['name' => $ingredient['name']], $ingredient);
        }

        // Create Ingredient Inventory for all ingredients
        foreach ($createdIngredients as $ingredient) {
            IngredientInventory::query()->firstOrCreate([
                'ingredient_id' => $ingredient->id,
            ], [
                'current_stock' => $this->getRandomStock($ingredient->unit_type),
                'min_stock_level' => $this->getMinStockLevel($ingredient->unit_type),
                'max_stock_level' => $this->getMaxStockLevel($ingredient->unit_type),
                'unit_cost' => $this->getUnitCost($ingredient->name),
                'location' => 'Main Storage',
                'supplier_info' => 'Primary Supplier',
                'last_restocked_at' => now()->subDays(rand(1, 30)),
            ]);
        }

        // Create Products for each category
        $this->createPancitProducts($createdCategories['Pancit'], $createdIngredients);
        $this->createPastaProducts($createdCategories['Pasta'], $createdIngredients);
        $this->createDessertProducts($createdCategories['Desserts'], $createdIngredients);
        $this->createPicaPicaProducts($createdCategories['Pica-Pica'], $createdIngredients);
        $this->createSaladProducts($createdCategories['Salad'], $createdIngredients);
        $this->createFriesProducts($createdCategories['Fries'], $createdIngredients);
        $this->createBurgerProducts($createdCategories['Burger'], $createdIngredients);
        $this->createSandwichProducts($createdCategories['Sandwiches'], $createdIngredients);
        $this->createOmeletteProducts($createdCategories['Omelette'], $createdIngredients);
        $this->createSilogProducts($createdCategories['SILOG Meals'], $createdIngredients);
        $this->createHotPlateProducts($createdCategories['Hot Plates'], $createdIngredients);
        $this->createBeefRiceMeals($createdCategories['Rice Meals - Beef'], $createdIngredients);
        $this->createChickenRiceMeals($createdCategories['Rice Meals - Chicken'], $createdIngredients);
        $this->createOtherRiceMeals($createdCategories['Rice Meals - Others'], $createdIngredients);
        $this->createAddOnProducts($createdCategories['Add-ons'], $createdIngredients);

        $this->command->info('Comprehensive menu data seeded successfully!');
    }

    private function createPancitProducts(Category $category, array $ingredients): void
    {
        $products = [
            'Pancit Bihon (SOLO)' => 69,
            'Pancit Bihon (4-6 PAX)' => 349,
            'Pancit Canton (SOLO)' => 89,
            'Pancit Canton (4-6 PAX)' => 399,
            'Pancit Sotanghon (SOLO)' => 89,
            'Pancit Sotanghon (4-6 PAX)' => 399,
            'Pancit Miki (SOLO)' => 69,
            'Pancit Miki (4-6 PAX)' => 349,
            'Pancit Palabok (SOLO)' => 89,
            'Pancit Palabok (4-6 PAX)' => 399,
        ];

        foreach ($products as $name => $price) {
            $product = Product::query()->firstOrCreate([
                'name' => $name,
            ], [
                'category_id' => $category->id,
                'price' => $price,
                'description' => "Traditional Filipino noodle dish",
                'preparation_time' => 15,
                'is_active' => true,
                'sku' => 'PC-' . strtoupper(str_replace(' ', '-', $name)),
            ]);

            $this->addPancitRecipe($product, $ingredients, str_contains($name, '4-6 PAX'));
        }
    }

    private function addPancitRecipe(Product $product, array $ingredients, bool $isGroupServing): void
    {
        $multiplier = $isGroupServing ? 4 : 1;
        $recipe = [];

        if (str_contains($product->name, 'Bihon')) {
            $recipe = [
                $ingredients['Bihon Noodles']->id => 200 * $multiplier,
                $ingredients['Cabbage']->id => 100 * $multiplier,
                $ingredients['Carrots']->id => 50 * $multiplier,
                $ingredients['Onions']->id => 30 * $multiplier,
                $ingredients['Soy Sauce']->id => 30 * $multiplier,
                $ingredients['Garlic']->id => 20 * $multiplier,
                $ingredients['Oil']->id => 20 * $multiplier,
            ];
        } elseif (str_contains($product->name, 'Canton')) {
            $recipe = [
                $ingredients['Canton Noodles']->id => 200 * $multiplier,
                $ingredients['Cabbage']->id => 100 * $multiplier,
                $ingredients['Carrots']->id => 50 * $multiplier,
                $ingredients['Onions']->id => 30 * $multiplier,
                $ingredients['Soy Sauce']->id => 30 * $multiplier,
                $ingredients['Garlic']->id => 20 * $multiplier,
                $ingredients['Oil']->id => 20 * $multiplier,
            ];
        } elseif (str_contains($product->name, 'Sotanghon')) {
            $recipe = [
                $ingredients['Sotanghon Noodles']->id => 150 * $multiplier,
                $ingredients['Cabbage']->id => 100 * $multiplier,
                $ingredients['Carrots']->id => 50 * $multiplier,
                $ingredients['Onions']->id => 30 * $multiplier,
                $ingredients['Soy Sauce']->id => 30 * $multiplier,
                $ingredients['Garlic']->id => 20 * $multiplier,
                $ingredients['Oil']->id => 20 * $multiplier,
            ];
        } elseif (str_contains($product->name, 'Miki')) {
            $recipe = [
                $ingredients['Miki Noodles']->id => 250 * $multiplier,
                $ingredients['Cabbage']->id => 100 * $multiplier,
                $ingredients['Carrots']->id => 50 * $multiplier,
                $ingredients['Onions']->id => 30 * $multiplier,
                $ingredients['Soy Sauce']->id => 30 * $multiplier,
                $ingredients['Garlic']->id => 20 * $multiplier,
                $ingredients['Oil']->id => 20 * $multiplier,
            ];
        } elseif (str_contains($product->name, 'Palabok')) {
            $recipe = [
                $ingredients['Pancit Noodles Mix']->id => 200 * $multiplier,
                $ingredients['Pancit Sauce']->id => 100 * $multiplier,
                $ingredients['Garlic']->id => 20 * $multiplier,
                $ingredients['Onions']->id => 30 * $multiplier,
                $ingredients['Oil']->id => 20 * $multiplier,
            ];
        }

        foreach ($recipe as $ingredientId => $quantity) {
            ProductIngredient::query()->firstOrCreate([
                'product_id' => $product->id,
                'ingredient_id' => $ingredientId,
            ], [
                'quantity_required' => $quantity,
            ]);
        }
    }

    private function createPastaProducts(Category $category, array $ingredients): void
    {
        $products = [
            'Beef Spaghetti' => 169,
            'Beef Stroganoff' => 179,
            'Carbonara' => 169,
        ];

        foreach ($products as $name => $price) {
            $product = Product::query()->firstOrCreate([
                'name' => $name,
            ], [
                'category_id' => $category->id,
                'price' => $price,
                'description' => "Classic pasta dish",
                'preparation_time' => 20,
                'is_active' => true,
                'sku' => 'PS-' . strtoupper(str_replace(' ', '-', $name)),
            ]);

            $this->addPastaRecipe($product, $ingredients);
        }
    }

    private function addPastaRecipe(Product $product, array $ingredients): void
    {
        if (str_contains($product->name, 'Spaghetti')) {
            $recipe = [
                $ingredients['Spaghetti Pasta']->id => 200,
                $ingredients['Beef Ground']->id => 150,
                $ingredients['Tomato Sauce']->id => 150,
                $ingredients['Onions']->id => 30,
                $ingredients['Garlic']->id => 20,
                $ingredients['Cheese']->id => 50,
            ];
        } elseif (str_contains($product->name, 'Stroganoff')) {
            $recipe = [
                $ingredients['Fettuccine Pasta']->id => 200,
                $ingredients['Beef Strips']->id => 200,
                $ingredients['Mushrooms']->id => 100,
                $ingredients['Heavy Cream']->id => 100,
                $ingredients['Onions']->id => 30,
                $ingredients['Garlic']->id => 20,
            ];
        } elseif (str_contains($product->name, 'Carbonara')) {
            $recipe = [
                $ingredients['Spaghetti Pasta']->id => 200,
                $ingredients['Bacon']->id => 100,
                $ingredients['Heavy Cream']->id => 150,
                $ingredients['Eggs']->id => 2,
                $ingredients['Garlic']->id => 20,
                $ingredients['Cheese']->id => 50,
            ];
        }

        foreach ($recipe as $ingredientId => $quantity) {
            ProductIngredient::query()->firstOrCreate([
                'product_id' => $product->id,
                'ingredient_id' => $ingredientId,
            ], [
                'quantity_required' => $quantity,
            ]);
        }
    }

    private function createDessertProducts(Category $category, array $ingredients): void
    {
        $products = [
            'Banana Bread' => 45,
            'Brownies' => 55,
            'Chocolate Chip Cookie' => 60,
            'Crinkles' => 60,
            'Matcha Choco Cookie' => 60,
            'Moist Choco Cupcake' => 65,
            'Red Velvet Cupcake with Cream Cheese' => 90,
        ];

        foreach ($products as $name => $price) {
            $product = Product::query()->firstOrCreate([
                'name' => $name,
            ], [
                'category_id' => $category->id,
                'price' => $price,
                'description' => "Sweet homemade dessert",
                'preparation_time' => 5,
                'is_active' => true,
                'sku' => 'DS-' . strtoupper(str_replace(' ', '-', $name)),
            ]);

            $this->addDessertRecipe($product, $ingredients);
        }
    }

    private function addDessertRecipe(Product $product, array $ingredients): void
    {
        if (str_contains($product->name, 'Banana Bread')) {
            $recipe = [
                $ingredients['Flour']->id => 200,
                $ingredients['Banana']->id => 200,
                $ingredients['Sugar']->id => 100,
                $ingredients['Eggs']->id => 2,
                $ingredients['Butter']->id => 100,
                $ingredients['Baking Powder']->id => 5,
            ];
        } elseif (str_contains($product->name, 'Brownies')) {
            $recipe = [
                $ingredients['Flour']->id => 150,
                $ingredients['Cocoa Powder']->id => 50,
                $ingredients['Sugar']->id => 150,
                $ingredients['Eggs']->id => 2,
                $ingredients['Butter']->id => 100,
                $ingredients['Chocolate Chips']->id => 50,
            ];
        } elseif (str_contains($product->name, 'Chocolate Chip Cookie')) {
            $recipe = [
                $ingredients['Flour']->id => 100,
                $ingredients['Sugar']->id => 50,
                $ingredients['Brown Sugar']->id => 50,
                $ingredients['Butter']->id => 50,
                $ingredients['Eggs']->id => 1,
                $ingredients['Chocolate Chips']->id => 30,
            ];
        } elseif (str_contains($product->name, 'Crinkles')) {
            $recipe = [
                $ingredients['Flour']->id => 100,
                $ingredients['Cocoa Powder']->id => 50,
                $ingredients['Sugar']->id => 100,
                $ingredients['Eggs']->id => 2,
                $ingredients['Oil']->id => 50,
                $ingredients['Baking Powder']->id => 2,
            ];
        } elseif (str_contains($product->name, 'Matcha Choco Cookie')) {
            $recipe = [
                $ingredients['Flour']->id => 100,
                $ingredients['Matcha Powder']->id => 20,
                $ingredients['Sugar']->id => 80,
                $ingredients['Butter']->id => 50,
                $ingredients['Eggs']->id => 1,
                $ingredients['Chocolate Chips']->id => 30,
            ];
        } elseif (str_contains($product->name, 'Moist Choco Cupcake')) {
            $recipe = [
                $ingredients['Flour']->id => 80,
                $ingredients['Cocoa Powder']->id => 30,
                $ingredients['Sugar']->id => 80,
                $ingredients['Eggs']->id => 1,
                $ingredients['Milk']->id => 50,
                $ingredients['Oil']->id => 30,
                $ingredients['Baking Powder']->id => 3,
            ];
        } elseif (str_contains($product->name, 'Red Velvet Cupcake')) {
            $recipe = [
                $ingredients['Flour']->id => 80,
                $ingredients['Sugar']->id => 80,
                $ingredients['Eggs']->id => 1,
                $ingredients['Butter']->id => 50,
                $ingredients['Cream Cheese']->id => 30,
                $ingredients['Milk']->id => 50,
            ];
        }

        foreach ($recipe as $ingredientId => $quantity) {
            ProductIngredient::query()->firstOrCreate([
                'product_id' => $product->id,
                'ingredient_id' => $ingredientId,
            ], [
                'quantity_required' => $quantity,
            ]);
        }
    }

    private function createPicaPicaProducts(Category $category, array $ingredients): void
    {
        $products = [
            'Chicken Tenders' => 179,
            'Nuggets' => 200,
            'Tempura' => 250,
        ];

        foreach ($products as $name => $price) {
            $product = Product::query()->firstOrCreate([
                'name' => $name,
            ], [
                'category_id' => $category->id,
                'price' => $price,
                'description' => "Crispy appetizer",
                'preparation_time' => 15,
                'is_active' => true,
                'sku' => 'PP-' . strtoupper(str_replace(' ', '-', $name)),
            ]);

            $this->addPicaPicaRecipe($product, $ingredients);
        }
    }

    private function addPicaPicaRecipe(Product $product, array $ingredients): void
    {
        if (str_contains($product->name, 'Chicken Tenders')) {
            $recipe = [
                $ingredients['Chicken Tenders']->id => 6,
                $ingredients['Flour']->id => 50,
                $ingredients['Eggs']->id => 1,
                $ingredients['Oil']->id => 100,
            ];
        } elseif (str_contains($product->name, 'Nuggets')) {
            $recipe = [
                $ingredients['Chicken Nuggets']->id => 10,
                $ingredients['Flour']->id => 30,
                $ingredients['Eggs']->id => 1,
                $ingredients['Oil']->id => 100,
            ];
        } elseif (str_contains($product->name, 'Tempura')) {
            $recipe = [
                $ingredients['Tempura Shrimp']->id => 8,
                $ingredients['Flour']->id => 50,
                $ingredients['Eggs']->id => 1,
                $ingredients['Oil']->id => 100,
            ];
        }

        foreach ($recipe as $ingredientId => $quantity) {
            ProductIngredient::query()->firstOrCreate([
                'product_id' => $product->id,
                'ingredient_id' => $ingredientId,
            ], [
                'quantity_required' => $quantity,
            ]);
        }
    }

    private function createSaladProducts(Category $category, array $ingredients): void
    {
        $products = [
            'Waldorf Salad' => 200,
            'Ribbon Salad' => 200,
            'Fruit Salad' => 200,
            'Chicken Salad' => 200,
            'Potato Salad' => 200,
            'Green Salad' => 170,
        ];

        foreach ($products as $name => $price) {
            $product = Product::query()->firstOrCreate([
                'name' => $name,
            ], [
                'category_id' => $category->id,
                'price' => $price,
                'description' => "Fresh and healthy salad",
                'preparation_time' => 10,
                'is_active' => true,
                'sku' => 'SL-' . strtoupper(str_replace(' ', '-', $name)),
            ]);

            $this->addSaladRecipe($product, $ingredients);
        }
    }

    private function addSaladRecipe(Product $product, array $ingredients): void
    {
        if (str_contains($product->name, 'Waldorf')) {
            $recipe = [
                $ingredients['Apples']->id => 100,
                $ingredients['Walnuts']->id => 30,
                $ingredients['Lettuce']->id => 50,
                $ingredients['Mayonnaise']->id => 50,
            ];
        } elseif (str_contains($product->name, 'Ribbon')) {
            $recipe = [
                $ingredients['Carrots']->id => 100,
                $ingredients['Cucumber']->id => 100,
                $ingredients['Lettuce']->id => 50,
                $ingredients['Mayonnaise']->id => 50,
            ];
        } elseif (str_contains($product->name, 'Fruit')) {
            $recipe = [
                $ingredients['Apples']->id => 100,
                $ingredients['Banana']->id => 100,
                $ingredients['Grapes']->id => 100,
                $ingredients['Cream']->id => 50,
                $ingredients['Sugar']->id => 30,
            ];
        } elseif (str_contains($product->name, 'Chicken')) {
            $recipe = [
                $ingredients['Chicken Breast']->id => 150,
                $ingredients['Lettuce']->id => 100,
                $ingredients['Mayonnaise']->id => 50,
                $ingredients['Celery']->id => 30,
            ];
        } elseif (str_contains($product->name, 'Potato')) {
            $recipe = [
                $ingredients['Potatoes']->id => 200,
                $ingredients['Mayonnaise']->id => 80,
                $ingredients['Eggs']->id => 2,
                $ingredients['Onions']->id => 30,
            ];
        } elseif (str_contains($product->name, 'Green')) {
            $recipe = [
                $ingredients['Lettuce']->id => 150,
                $ingredients['Tomatoes']->id => 50,
                $ingredients['Cucumber']->id => 50,
                $ingredients['Onions']->id => 20,
                $ingredients['Oil']->id => 30,
            ];
        }

        foreach ($recipe as $ingredientId => $quantity) {
            ProductIngredient::query()->firstOrCreate([
                'product_id' => $product->id,
                'ingredient_id' => $ingredientId,
            ], [
                'quantity_required' => $quantity,
            ]);
        }
    }

    private function createFriesProducts(Category $category, array $ingredients): void
    {
        $products = [
            'Potato Fries' => 120,
            'Camote Fries' => 120,
            'Hashbrown' => 90,
        ];

        foreach ($products as $name => $price) {
            $product = Product::query()->firstOrCreate([
                'name' => $name,
            ], [
                'category_id' => $category->id,
                'price' => $price,
                'description' => "Crispy fried potatoes",
                'preparation_time' => 8,
                'is_active' => true,
                'sku' => 'FR-' . strtoupper(str_replace(' ', '-', $name)),
            ]);

            $this->addFriesRecipe($product, $ingredients);
        }
    }

    private function addFriesRecipe(Product $product, array $ingredients): void
    {
        if (str_contains($product->name, 'Potato')) {
            $recipe = [
                $ingredients['Potatoes']->id => 200,
                $ingredients['Oil']->id => 100,
                $ingredients['Salt']->id => 5,
            ];
        } elseif (str_contains($product->name, 'Camote')) {
            $recipe = [
                $ingredients['Sweet Potato']->id => 200,
                $ingredients['Oil']->id => 100,
                $ingredients['Salt']->id => 5,
            ];
        } elseif (str_contains($product->name, 'Hashbrown')) {
            $recipe = [
                $ingredients['Potatoes']->id => 150,
                $ingredients['Oil']->id => 80,
                $ingredients['Salt']->id => 3,
            ];
        }

        foreach ($recipe as $ingredientId => $quantity) {
            ProductIngredient::query()->firstOrCreate([
                'product_id' => $product->id,
                'ingredient_id' => $ingredientId,
            ], [
                'quantity_required' => $quantity,
            ]);
        }
    }

    private function createBurgerProducts(Category $category, array $ingredients): void
    {
        $products = [
            'Beefy Cheese Burger' => 169,
            'Zucchini Beef Burger' => 159,
        ];

        foreach ($products as $name => $price) {
            $product = Product::query()->firstOrCreate([
                'name' => $name,
            ], [
                'category_id' => $category->id,
                'price' => $price,
                'description' => "Gourmet burger",
                'preparation_time' => 12,
                'is_active' => true,
                'sku' => 'BG-' . strtoupper(str_replace(' ', '-', $name)),
            ]);

            $this->addBurgerRecipe($product, $ingredients);
        }
    }

    private function addBurgerRecipe(Product $product, array $ingredients): void
    {
        if (str_contains($product->name, 'Beefy Cheese')) {
            $recipe = [
                $ingredients['Beef Ground']->id => 200,
                $ingredients['Cheese']->id => 50,
                $ingredients['Lettuce']->id => 30,
                $ingredients['Tomatoes']->id => 30,
                $ingredients['Onions']->id => 20,
                $ingredients['Buns']->id => 2,
                $ingredients['Ketchup']->id => 20,
                $ingredients['Mayonnaise']->id => 20,
            ];
        } elseif (str_contains($product->name, 'Zucchini')) {
            $recipe = [
                $ingredients['Beef Ground']->id => 150,
                $ingredients['Zucchini']->id => 100,
                $ingredients['Cheese']->id => 40,
                $ingredients['Lettuce']->id => 30,
                $ingredients['Tomatoes']->id => 30,
                $ingredients['Buns']->id => 2,
                $ingredients['Ketchup']->id => 20,
            ];
        }

        foreach ($recipe as $ingredientId => $quantity) {
            ProductIngredient::query()->firstOrCreate([
                'product_id' => $product->id,
                'ingredient_id' => $ingredientId,
            ], [
                'quantity_required' => $quantity,
            ]);
        }
    }

    private function createSandwichProducts(Category $category, array $ingredients): void
    {
        $products = [
            'Chicken Sandwich' => 145,
            'Tuna Sandwich' => 145,
            'Ham & Cheese Sandwich' => 69,
            'Ham & Egg Sandwich' => 75,
        ];

        foreach ($products as $name => $price) {
            $product = Product::query()->firstOrCreate([
                'name' => $name,
            ], [
                'category_id' => $category->id,
                'price' => $price,
                'description' => "Fresh sandwich",
                'preparation_time' => 8,
                'is_active' => true,
                'sku' => 'SW-' . strtoupper(str_replace(' ', '-', $name)),
            ]);

            $this->addSandwichRecipe($product, $ingredients);
        }
    }

    private function addSandwichRecipe(Product $product, array $ingredients): void
    {
        if (str_contains($product->name, 'Chicken')) {
            $recipe = [
                $ingredients['Chicken Breast']->id => 150,
                $ingredients['Bread']->id => 2,
                $ingredients['Lettuce']->id => 30,
                $ingredients['Mayonnaise']->id => 30,
                $ingredients['Tomatoes']->id => 20,
            ];
        } elseif (str_contains($product->name, 'Tuna')) {
            $recipe = [
                $ingredients['Tuna Flakes']->id => 100,
                $ingredients['Bread']->id => 2,
                $ingredients['Mayonnaise']->id => 30,
                $ingredients['Onions']->id => 20,
                $ingredients['Lettuce']->id => 20,
            ];
        } elseif (str_contains($product->name, 'Ham & Cheese')) {
            $recipe = [
                $ingredients['Ham']->id => 100,
                $ingredients['Cheese']->id => 40,
                $ingredients['Bread']->id => 2,
                $ingredients['Mayonnaise']->id => 20,
            ];
        } elseif (str_contains($product->name, 'Ham & Egg')) {
            $recipe = [
                $ingredients['Ham']->id => 80,
                $ingredients['Eggs']->id => 2,
                $ingredients['Bread']->id => 2,
                $ingredients['Mayonnaise']->id => 20,
            ];
        }

        foreach ($recipe as $ingredientId => $quantity) {
            ProductIngredient::query()->firstOrCreate([
                'product_id' => $product->id,
                'ingredient_id' => $ingredientId,
            ], [
                'quantity_required' => $quantity,
            ]);
        }
    }

    private function createOmeletteProducts(Category $category, array $ingredients): void
    {
        $products = [
            'Cheesy Spam Omelette' => 129,
            'Chicken Omelette' => 129,
            'Corned Beef Omelette' => 129,
            'Tuna Omelette' => 129,
            'Veggies Omelette' => 129,
        ];

        foreach ($products as $name => $price) {
            $product = Product::query()->firstOrCreate([
                'name' => $name,
            ], [
                'category_id' => $category->id,
                'price' => $price,
                'description' => "Fluffy omelette",
                'preparation_time' => 10,
                'is_active' => true,
                'sku' => 'OM-' . strtoupper(str_replace(' ', '-', $name)),
            ]);

            $this->addOmeletteRecipe($product, $ingredients);
        }
    }

    private function addOmeletteRecipe(Product $product, array $ingredients): void
    {
        $baseRecipe = [
            $ingredients['Eggs']->id => 3,
            $ingredients['Oil']->id => 20,
            $ingredients['Salt']->id => 2,
            $ingredients['Pepper']->id => 1,
        ];

        if (str_contains($product->name, 'Cheesy Spam')) {
            $recipe = array_merge($baseRecipe, [
                $ingredients['Spam']->id => 50,
                $ingredients['Cheese']->id => 40,
            ]);
        } elseif (str_contains($product->name, 'Chicken')) {
            $recipe = array_merge($baseRecipe, [
                $ingredients['Chicken Breast']->id => 100,
                $ingredients['Onions']->id => 20,
                $ingredients['Garlic']->id => 10,
            ]);
        } elseif (str_contains($product->name, 'Corned Beef')) {
            $recipe = array_merge($baseRecipe, [
                $ingredients['Corned Beef']->id => 100,
                $ingredients['Onions']->id => 20,
                $ingredients['Garlic']->id => 10,
            ]);
        } elseif (str_contains($product->name, 'Tuna')) {
            $recipe = array_merge($baseRecipe, [
                $ingredients['Tuna Flakes']->id => 80,
                $ingredients['Onions']->id => 20,
                $ingredients['Garlic']->id => 10,
            ]);
        } elseif (str_contains($product->name, 'Veggies')) {
            $recipe = array_merge($baseRecipe, [
                $ingredients['Cabbage']->id => 50,
                $ingredients['Carrots']->id => 30,
                $ingredients['Onions']->id => 20,
                $ingredients['Garlic']->id => 10,
            ]);
        }

        foreach ($recipe as $ingredientId => $quantity) {
            ProductIngredient::query()->firstOrCreate([
                'product_id' => $product->id,
                'ingredient_id' => $ingredientId,
            ], [
                'quantity_required' => $quantity,
            ]);
        }
    }

    private function createSilogProducts(Category $category, array $ingredients): void
    {
        $products = [
            'Dilis-Silog' => 185,
            'Pusit-Silog' => 185,
            'Tuyo-Silog' => 185,
            'Tocilog' => 185,
            'Tapsilog' => 185,
            'Vigan Longsilog' => 185,
        ];

        foreach ($products as $name => $price) {
            $product = Product::query()->firstOrCreate([
                'name' => $name,
            ], [
                'category_id' => $category->id,
                'price' => $price,
                'description' => "Filipino breakfast combo",
                'preparation_time' => 15,
                'is_active' => true,
                'sku' => 'SG-' . strtoupper(str_replace(' ', '-', $name)),
            ]);

            $this->addSilogRecipe($product, $ingredients);
        }
    }

    private function addSilogRecipe(Product $product, array $ingredients): void
    {
        $baseRecipe = [
            $ingredients['Garlic Rice']->id => 250,
            $ingredients['Eggs']->id => 2,
            $ingredients['Garlic']->id => 20,
            $ingredients['Oil']->id => 30,
            $ingredients['Vinegar']->id => 20,
        ];

        if (str_contains($product->name, 'Dilis')) {
            $recipe = array_merge($baseRecipe, [
                $ingredients['Dilis']->id => 50,
            ]);
        } elseif (str_contains($product->name, 'Pusit')) {
            $recipe = array_merge($baseRecipe, [
                $ingredients['Pusit']->id => 100,
            ]);
        } elseif (str_contains($product->name, 'Tuyo')) {
            $recipe = array_merge($baseRecipe, [
                $ingredients['Tuyo']->id => 50,
            ]);
        } elseif (str_contains($product->name, 'Tocilog')) {
            $recipe = array_merge($baseRecipe, [
                $ingredients['Longganisa']->id => 2,
            ]);
        } elseif (str_contains($product->name, 'Tapsilog')) {
            $recipe = array_merge($baseRecipe, [
                $ingredients['Tapa']->id => 150,
            ]);
        } elseif (str_contains($product->name, 'Vigan Longsilog')) {
            $recipe = array_merge($baseRecipe, [
                $ingredients['Longganisa']->id => 2,
            ]);
        }

        foreach ($recipe as $ingredientId => $quantity) {
            ProductIngredient::query()->firstOrCreate([
                'product_id' => $product->id,
                'ingredient_id' => $ingredientId,
            ], [
                'quantity_required' => $quantity,
            ]);
        }
    }

    private function createHotPlateProducts(Category $category, array $ingredients): void
    {
        $products = [
            'Chicken Hot Plate' => 179,
            'Liempo Hot Plate' => 199,
            'Porkchop Hot Plate' => 199,
        ];

        foreach ($products as $name => $price) {
            $product = Product::query()->firstOrCreate([
                'name' => $name,
            ], [
                'category_id' => $category->id,
                'price' => $price,
                'description' => "Sizzling hot plate",
                'preparation_time' => 20,
                'is_active' => true,
                'sku' => 'HP-' . strtoupper(str_replace(' ', '-', $name)),
            ]);

            $this->addHotPlateRecipe($product, $ingredients);
        }
    }

    private function addHotPlateRecipe(Product $product, array $ingredients): void
    {
        $baseRecipe = [
            $ingredients['Rice']->id => 250,
            $ingredients['Vegetables']->id => 100,
            $ingredients['Sauce']->id => 50,
        ];

        if (str_contains($product->name, 'Chicken')) {
            $recipe = array_merge($baseRecipe, [
                $ingredients['Chicken Breast']->id => 200,
            ]);
        } elseif (str_contains($product->name, 'Liempo')) {
            $recipe = array_merge($baseRecipe, [
                $ingredients['Pork Liempo']->id => 200,
            ]);
        } elseif (str_contains($product->name, 'Porkchop')) {
            $recipe = array_merge($baseRecipe, [
                $ingredients['Porkchop']->id => 200,
            ]);
        }

        foreach ($recipe as $ingredientId => $quantity) {
            ProductIngredient::query()->firstOrCreate([
                'product_id' => $product->id,
                'ingredient_id' => $ingredientId,
            ], [
                'quantity_required' => $quantity,
            ]);
        }
    }

    private function createBeefRiceMeals(Category $category, array $ingredients): void
    {
        $products = [
            'Beef Broccoli' => 169,
            'Beef Mushroom' => 169,
            'Boneless Bangus' => 120,
            'Beef in Oyster Sauce' => 169,
            'Beef Brisket' => 169,
            'Beef Stroganoff' => 169,
        ];

        foreach ($products as $name => $price) {
            $product = Product::query()->firstOrCreate([
                'name' => $name,
            ], [
                'category_id' => $category->id,
                'price' => $price,
                'description' => "Beef dish with rice",
                'preparation_time' => 18,
                'is_active' => true,
                'sku' => 'BR-' . strtoupper(str_replace(' ', '-', $name)),
            ]);

            $this->addBeefRiceMealRecipe($product, $ingredients);
        }
    }

    private function addBeefRiceMealRecipe(Product $product, array $ingredients): void
    {
        $baseRecipe = [
            $ingredients['Rice']->id => 250,
        ];

        if (str_contains($product->name, 'Beef Broccoli')) {
            $recipe = array_merge($baseRecipe, [
                $ingredients['Beef Strips']->id => 150,
                $ingredients['Broccoli']->id => 100,
                $ingredients['Garlic']->id => 20,
                $ingredients['Oyster Sauce']->id => 30,
                $ingredients['Oil']->id => 30,
            ]);
        } elseif (str_contains($product->name, 'Beef Mushroom')) {
            $recipe = array_merge($baseRecipe, [
                $ingredients['Beef Strips']->id => 150,
                $ingredients['Mushrooms']->id => 100,
                $ingredients['Garlic']->id => 20,
                $ingredients['Oyster Sauce']->id => 30,
                $ingredients['Oil']->id => 30,
            ]);
        } elseif (str_contains($product->name, 'Boneless Bangus')) {
            $recipe = array_merge($baseRecipe, [
                $ingredients['Boneless Bangus']->id => 200,
                $ingredients['Garlic']->id => 20,
                $ingredients['Oil']->id => 30,
                $ingredients['Vinegar']->id => 20,
            ]);
        } elseif (str_contains($product->name, 'Beef in Oyster Sauce')) {
            $recipe = array_merge($baseRecipe, [
                $ingredients['Beef Strips']->id => 150,
                $ingredients['Garlic']->id => 20,
                $ingredients['Oyster Sauce']->id => 50,
                $ingredients['Oil']->id => 30,
            ]);
        } elseif (str_contains($product->name, 'Beef Brisket')) {
            $recipe = array_merge($baseRecipe, [
                $ingredients['Beef Brisket']->id => 200,
                $ingredients['Garlic']->id => 20,
                $ingredients['Soy Sauce']->id => 30,
                $ingredients['Oil']->id => 30,
            ]);
        } elseif (str_contains($product->name, 'Beef Stroganoff')) {
            $recipe = array_merge($baseRecipe, [
                $ingredients['Beef Strips']->id => 150,
                $ingredients['Mushrooms']->id => 80,
                $ingredients['Heavy Cream']->id => 80,
                $ingredients['Garlic']->id => 20,
                $ingredients['Oil']->id => 30,
            ]);
        }

        foreach ($recipe as $ingredientId => $quantity) {
            ProductIngredient::query()->firstOrCreate([
                'product_id' => $product->id,
                'ingredient_id' => $ingredientId,
            ], [
                'quantity_required' => $quantity,
            ]);
        }
    }

    private function createChickenRiceMeals(Category $category, array $ingredients): void
    {
        $products = [
            'Chicken Pork Adobo' => 169,
            'Chili Garlic Chicken' => 169,
            'Chicken Curry' => 169,
            'Chicken Pastil' => 169,
            'Crab Rice' => 169,
            'Honey Ginger Chicken' => 169,
        ];

        foreach ($products as $name => $price) {
            $product = Product::query()->firstOrCreate([
                'name' => $name,
            ], [
                'category_id' => $category->id,
                'price' => $price,
                'description' => "Chicken dish with rice",
                'preparation_time' => 16,
                'is_active' => true,
                'sku' => 'CR-' . strtoupper(str_replace(' ', '-', $name)),
            ]);

            $this->addChickenRiceMealRecipe($product, $ingredients);
        }
    }

    private function addChickenRiceMealRecipe(Product $product, array $ingredients): void
    {
        $baseRecipe = [
            $ingredients['Rice']->id => 250,
        ];

        if (str_contains($product->name, 'Adobo')) {
            $recipe = array_merge($baseRecipe, [
                $ingredients['Chicken Breast']->id => 150,
                $ingredients['Pork Liempo']->id => 100,
                $ingredients['Garlic']->id => 30,
                $ingredients['Soy Sauce']->id => 50,
                $ingredients['Vinegar']->id => 30,
                $ingredients['Oil']->id => 30,
            ]);
        } elseif (str_contains($product->name, 'Chili Garlic')) {
            $recipe = array_merge($baseRecipe, [
                $ingredients['Chicken Breast']->id => 200,
                $ingredients['Garlic']->id => 50,
                $ingredients['Chili']->id => 20,
                $ingredients['Oil']->id => 40,
            ]);
        } elseif (str_contains($product->name, 'Chicken Curry')) {
            $recipe = array_merge($baseRecipe, [
                $ingredients['Chicken Breast']->id => 200,
                $ingredients['Curry Powder']->id => 20,
                $ingredients['Coconut Milk']->id => 150,
                $ingredients['Garlic']->id => 20,
                $ingredients['Oil']->id => 30,
            ]);
        } elseif (str_contains($product->name, 'Chicken Pastil')) {
            $recipe = array_merge($baseRecipe, [
                $ingredients['Chicken Breast']->id => 150,
                $ingredients['Garlic']->id => 20,
                $ingredients['Turmeric']->id => 10,
                $ingredients['Oil']->id => 30,
            ]);
        } elseif (str_contains($product->name, 'Crab Rice')) {
            $recipe = [
                $ingredients['Rice']->id => 250,
                $ingredients['Crab Meat']->id => 100,
                $ingredients['Garlic']->id => 30,
                $ingredients['Oil']->id => 40,
            ];
        } elseif (str_contains($product->name, 'Honey Ginger')) {
            $recipe = array_merge($baseRecipe, [
                $ingredients['Chicken Breast']->id => 200,
                $ingredients['Ginger']->id => 30,
                $ingredients['Honey']->id => 30,
                $ingredients['Oil']->id => 30,
            ]);
        }

        foreach ($recipe as $ingredientId => $quantity) {
            ProductIngredient::query()->firstOrCreate([
                'product_id' => $product->id,
                'ingredient_id' => $ingredientId,
            ], [
                'quantity_required' => $quantity,
            ]);
        }
    }

    private function createOtherRiceMeals(Category $category, array $ingredients): void
    {
        $products = [
            'Pandan Chicken' => 169,
            'Pork Satay' => 169,
            'Spicy Basil Beef' => 169,
            'Sweet & Sour Pork' => 169,
            'Spicy Pork' => 169,
        ];

        foreach ($products as $name => $price) {
            $product = Product::query()->firstOrCreate([
                'name' => $name,
            ], [
                'category_id' => $category->id,
                'price' => $price,
                'description' => "Specialty rice meal",
                'preparation_time' => 18,
                'is_active' => true,
                'sku' => 'OR-' . strtoupper(str_replace(' ', '-', $name)),
            ]);

            $this->addOtherRiceMealRecipe($product, $ingredients);
        }
    }

    private function addOtherRiceMealRecipe(Product $product, array $ingredients): void
    {
        $baseRecipe = [
            $ingredients['Rice']->id => 250,
        ];

        if (str_contains($product->name, 'Pandan Chicken')) {
            $recipe = array_merge($baseRecipe, [
                $ingredients['Chicken Breast']->id => 200,
                $ingredients['Pandan Leaves']->id => 4,
                $ingredients['Garlic']->id => 20,
                $ingredients['Oil']->id => 30,
            ]);
        } elseif (str_contains($product->name, 'Pork Satay')) {
            $recipe = array_merge($baseRecipe, [
                $ingredients['Pork Satay']->id => 200,
                $ingredients['Peanut Sauce']->id => 50,
                $ingredients['Garlic']->id => 20,
                $ingredients['Oil']->id => 30,
            ]);
        } elseif (str_contains($product->name, 'Spicy Basil Beef')) {
            $recipe = array_merge($baseRecipe, [
                $ingredients['Beef Strips']->id => 150,
                $ingredients['Basil']->id => 20,
                $ingredients['Chili']->id => 30,
                $ingredients['Garlic']->id => 20,
                $ingredients['Oil']->id => 30,
            ]);
        } elseif (str_contains($product->name, 'Sweet & Sour Pork')) {
            $recipe = array_merge($baseRecipe, [
                $ingredients['Pork Liempo']->id => 200,
                $ingredients['Bell Peppers']->id => 50,
                $ingredients['Pineapple']->id => 50,
                $ingredients['Vinegar']->id => 30,
                $ingredients['Sugar']->id => 30,
                $ingredients['Oil']->id => 30,
            ]);
        } elseif (str_contains($product->name, 'Spicy Pork')) {
            $recipe = array_merge($baseRecipe, [
                $ingredients['Pork Liempo']->id => 200,
                $ingredients['Chili']->id => 50,
                $ingredients['Garlic']->id => 30,
                $ingredients['Oil']->id => 40,
            ]);
        }

        foreach ($recipe as $ingredientId => $quantity) {
            ProductIngredient::query()->firstOrCreate([
                'product_id' => $product->id,
                'ingredient_id' => $ingredientId,
            ], [
                'quantity_required' => $quantity,
            ]);
        }
    }

    private function createAddOnProducts(Category $category, array $ingredients): void
    {
        $products = [
            'Plain Rice' => 25,
            'Fried Rice' => 35,
        ];

        foreach ($products as $name => $price) {
            $product = Product::query()->firstOrCreate([
                'name' => $name,
            ], [
                'category_id' => $category->id,
                'price' => $price,
                'description' => "Side dish",
                'preparation_time' => 5,
                'is_active' => true,
                'sku' => 'AO-' . strtoupper(str_replace(' ', '-', $name)),
            ]);

            $this->addAddOnRecipe($product, $ingredients);
        }
    }

    private function addAddOnRecipe(Product $product, array $ingredients): void
    {
        if (str_contains($product->name, 'Plain Rice')) {
            $recipe = [
                $ingredients['Rice']->id => 250,
            ];
        } elseif (str_contains($product->name, 'Fried Rice')) {
            $recipe = [
                $ingredients['Fried Rice Mix']->id => 250,
                $ingredients['Garlic']->id => 20,
                $ingredients['Oil']->id => 20,
                $ingredients['Eggs']->id => 1,
            ];
        }

        foreach ($recipe as $ingredientId => $quantity) {
            ProductIngredient::query()->firstOrCreate([
                'product_id' => $product->id,
                'ingredient_id' => $ingredientId,
            ], [
                'quantity_required' => $quantity,
            ]);
        }
    }

    private function getRandomStock(string $unitType): int
    {
        return match ($unitType) {
            'grams' => rand(5000, 50000),
            'kilograms' => rand(5, 50),
            'ml' => rand(1000, 20000),
            'liters' => rand(1, 20),
            'pieces' => rand(100, 1000),
            default => rand(100, 1000),
        };
    }

    private function getMinStockLevel(string $unitType): int
    {
        return match ($unitType) {
            'grams' => 1000,
            'kilograms' => 1,
            'ml' => 500,
            'liters' => 1,
            'pieces' => 50,
            default => 100,
        };
    }

    private function getMaxStockLevel(string $unitType): int
    {
        return match ($unitType) {
            'grams' => 100000,
            'kilograms' => 100,
            'ml' => 50000,
            'liters' => 50,
            'pieces' => 2000,
            default => 2000,
        };
    }

    private function getUnitCost(string $ingredientName): float
    {
        // Reasonable unit costs for different ingredients
        $costs = [
            // Noodles & Pasta
            'Bihon Noodles' => 0.002,
            'Canton Noodles' => 0.003,
            'Sotanghon Noodles' => 0.004,
            'Miki Noodles' => 0.0025,
            'Pancit Noodles Mix' => 0.003,
            'Spaghetti Pasta' => 0.002,
            'Fettuccine Pasta' => 0.003,

            // Meat & Protein
            'Beef Ground' => 0.015,
            'Beef Strips' => 0.018,
            'Beef Brisket' => 0.020,
            'Chicken Breast' => 0.012,
            'Chicken Thighs' => 0.010,
            'Pork Liempo' => 0.014,
            'Porkchop' => 0.016,
            'Bacon' => 0.025,
            'Ham' => 0.020,

            // Vegetables
            'Cabbage' => 0.003,
            'Carrots' => 0.004,
            'Onions' => 0.003,
            'Garlic' => 0.008,
            'Potatoes' => 0.003,
            'Tomatoes' => 0.005,

            // Dairy
            'Eggs' => 0.015,
            'Cheese' => 0.010,
            'Milk' => 0.001,

            // Dry goods
            'Flour' => 0.002,
            'Sugar' => 0.0015,
            'Rice' => 0.001,

            // Sauces & Oils
            'Oil' => 0.002,
            'Soy Sauce' => 0.003,
            'Oyster Sauce' => 0.005,
        ];

        return $costs[$ingredientName] ?? 0.005; // Default cost
    }
}
