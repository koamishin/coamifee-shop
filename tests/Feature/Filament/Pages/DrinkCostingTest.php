<?php

declare(strict_types=1);

use App\Filament\Pages\DrinkCosting;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\IngredientInventory;
use App\Models\Product;
use App\Models\ProductIngredient;
use App\Models\User;
use App\Services\DrinkCostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    actingAs($this->user);

    // Create a category
    $this->category = Category::factory()->create([
        'name' => 'Beverages',
        'is_active' => true,
    ]);

    // Create an ingredient with inventory (including unit_cost)
    $this->ingredient = Ingredient::factory()->create([
        'name' => 'Coffee Beans',
    ]);

    $this->inventory = IngredientInventory::factory()->create([
        'ingredient_id' => $this->ingredient->id,
        'unit_cost' => 0.50, // 50 cents per unit
    ]);

    // Create a product with the ingredient
    $this->product = Product::factory()->create([
        'name' => 'Espresso',
        'price' => 120.00,
        'category_id' => $this->category->id,
        'is_active' => true,
        'sku' => 'ESP-001',
    ]);

    // Link ingredient to product
    $this->productIngredient = ProductIngredient::factory()->create([
        'product_id' => $this->product->id,
        'ingredient_id' => $this->ingredient->id,
        'quantity_required' => 20.0, // 20 units of coffee beans
    ]);
});

test('drink costing page renders successfully', function (): void {
    Livewire::test(DrinkCosting::class)
        ->assertSuccessful()
        ->assertSeeHtml('Drink Costing');
});

test('drink costing page displays products with costing data', function (): void {
    Livewire::test(DrinkCosting::class)
        ->assertSuccessful()
        ->assertSee('Espresso')
        ->assertSee('Beverages');
});

test('drink costing page shows summary statistics', function (): void {
    Livewire::test(DrinkCosting::class)
        ->assertSuccessful()
        ->assertSee('Total Products');
});

test('drink costing page has settings action', function (): void {
    Livewire::test(DrinkCosting::class)
        ->assertSuccessful()
        ->assertActionExists('settings');
});

test('drink costing page has refresh action', function (): void {
    Livewire::test(DrinkCosting::class)
        ->assertSuccessful()
        ->assertActionExists('refresh');
});

test('drink costing page can filter by category', function (): void {
    // Create another category with a product
    $anotherCategory = Category::factory()->create([
        'name' => 'Pastries',
        'is_active' => true,
    ]);

    Product::factory()->create([
        'name' => 'Croissant',
        'price' => 80.00,
        'category_id' => $anotherCategory->id,
        'is_active' => true,
    ]);

    // Test filter by category
    Livewire::test(DrinkCosting::class)
        ->assertSee('Espresso')
        ->assertSee('Croissant')
        ->call('filterByCategory', $this->category->id)
        ->assertSee('Espresso')
        ->assertDontSee('Croissant');
});

test('drink costing page can reset category filter', function (): void {
    Livewire::test(DrinkCosting::class)
        ->call('filterByCategory', $this->category->id)
        ->call('filterByCategory', null)
        ->assertSee('Espresso');
});

test('drink costing service calculates ingredient cost correctly', function (): void {
    $service = new DrinkCostingService();

    $costing = $service->calculateProductCosting($this->product);

    // Ingredient cost = quantity_required (20) × unit_cost (0.50) = 10.00
    expect($costing['ingredient_cost'])->toBe(10.0);
});

test('drink costing service calculates labor cost correctly', function (): void {
    $service = new DrinkCostingService();

    $costing = $service->calculateProductCosting($this->product, 30.0); // 30% labor

    // Ingredient cost = 10.00
    // Labor cost = 10.00 × 30% = 3.00
    expect($costing['labor_cost'])->toBe(3.0);
});

test('drink costing service calculates total cost correctly', function (): void {
    $service = new DrinkCostingService();

    $costing = $service->calculateProductCosting($this->product, 30.0);

    // Total cost = ingredient (10) + labor (3) = 13.00
    expect($costing['total_cost'])->toBe(13.0);
});

test('drink costing service calculates gross profit correctly', function (): void {
    $service = new DrinkCostingService();

    $costing = $service->calculateProductCosting($this->product, 30.0);

    // Gross profit = menu price (120) - total cost (13) = 107.00
    expect($costing['gross_profit'])->toBe(107.0);
});

test('drink costing service calculates profit margin correctly', function (): void {
    $service = new DrinkCostingService();

    $costing = $service->calculateProductCosting($this->product, 30.0);

    // Profit margin = (107 / 120) × 100 = 89.17%
    expect($costing['profit_margin'])->toBeGreaterThan(89.0)
        ->and($costing['profit_margin'])->toBeLessThan(90.0);
});

test('drink costing service calculates food cost percentage correctly', function (): void {
    $service = new DrinkCostingService();

    $costing = $service->calculateProductCosting($this->product, 30.0);

    // Food cost % = (13 / 120) × 100 = 10.83%
    expect($costing['food_cost_percentage'])->toBeGreaterThan(10.0)
        ->and($costing['food_cost_percentage'])->toBeLessThan(11.0);
});

test('drink costing service determines price status correctly', function (): void {
    $service = new DrinkCostingService();

    // Product with high price should be above target
    $costing = $service->calculateProductCosting($this->product, 30.0, 40.0);

    // Suggested price = 13 + (13 × 0.40) = 18.20
    // Menu price = 120, which is way above suggested
    expect($costing['price_status'])->toBe('above_target');
});

test('drink costing service handles product without ingredients', function (): void {
    $productWithoutIngredients = Product::factory()->create([
        'name' => 'Simple Water',
        'price' => 20.00,
        'category_id' => $this->category->id,
        'is_active' => true,
    ]);

    $service = new DrinkCostingService();
    $costing = $service->calculateProductCosting($productWithoutIngredients);

    expect($costing['ingredient_cost'])->toBe(0.0)
        ->and($costing['labor_cost'])->toBe(0.0)
        ->and($costing['total_cost'])->toBe(0.0);
});

test('drink costing service returns ingredient breakdown', function (): void {
    $service = new DrinkCostingService();
    $costing = $service->calculateProductCosting($this->product);

    expect($costing['ingredients'])->toBeArray()
        ->and($costing['ingredients'])->toHaveCount(1)
        ->and($costing['ingredients'][0]['ingredient_name'])->toBe('Coffee Beans')
        ->and($costing['ingredients'][0]['quantity_required'])->toBe(20.0)
        ->and($costing['ingredients'][0]['unit_cost'])->toBe(0.5);
});

test('drink costing service returns summary statistics', function (): void {
    $service = new DrinkCostingService();
    $costings = $service->getDrinkCostings();
    $summary = $service->getSummaryStatistics($costings);

    expect($summary)->toHaveKey('total_products')
        ->and($summary)->toHaveKey('avg_ingredient_cost')
        ->and($summary)->toHaveKey('avg_labor_cost')
        ->and($summary)->toHaveKey('avg_total_cost')
        ->and($summary)->toHaveKey('avg_menu_price')
        ->and($summary)->toHaveKey('avg_gross_profit')
        ->and($summary)->toHaveKey('avg_profit_margin')
        ->and($summary)->toHaveKey('profitable_count')
        ->and($summary)->toHaveKey('unprofitable_count');
});

test('drink costing service returns empty summary for no products', function (): void {
    $service = new DrinkCostingService();
    $summary = $service->getSummaryStatistics(collect());

    expect($summary['total_products'])->toBe(0)
        ->and($summary['profitable_count'])->toBe(0)
        ->and($summary['unprofitable_count'])->toBe(0);
});

test('drink costing identifies profitable vs unprofitable products', function (): void {
    // Create an unprofitable product (price below cost)
    $unprofitableProduct = Product::factory()->create([
        'name' => 'Loss Leader',
        'price' => 5.00, // Very low price
        'category_id' => $this->category->id,
        'is_active' => true,
    ]);

    // Add expensive ingredient
    $expensiveIngredient = Ingredient::factory()->create(['name' => 'Expensive Item']);
    IngredientInventory::factory()->create([
        'ingredient_id' => $expensiveIngredient->id,
        'unit_cost' => 10.00, // 10 per unit
    ]);
    ProductIngredient::factory()->create([
        'product_id' => $unprofitableProduct->id,
        'ingredient_id' => $expensiveIngredient->id,
        'quantity_required' => 5.0, // Cost = 50
    ]);

    $service = new DrinkCostingService();
    $costing = $service->calculateProductCosting($unprofitableProduct);

    expect($costing['is_profitable'])->toBeFalse()
        ->and($costing['gross_profit'])->toBeLessThan(0);
});

test('drink costing page applies custom labor and markup percentages', function (): void {
    Livewire::test(DrinkCosting::class)
        ->assertSet('laborPercentage', 30.0)
        ->assertSet('markupPercentage', 40.0)
        ->callAction('settings', [
            'labor_percentage' => 25.0,
            'markup_percentage' => 50.0,
            'category_id' => '',
        ])
        ->assertSet('laborPercentage', 25.0)
        ->assertSet('markupPercentage', 50.0)
        ->assertNotified('Settings updated');
});

test('drink costing service filters by category', function (): void {
    // Create another category with products
    $anotherCategory = Category::factory()->create([
        'name' => 'Pastries',
        'is_active' => true,
    ]);

    Product::factory()->create([
        'name' => 'Croissant',
        'price' => 80.00,
        'category_id' => $anotherCategory->id,
        'is_active' => true,
    ]);

    $service = new DrinkCostingService();

    // Filter by Beverages category
    $costings = $service->getDrinkCostings($this->category->id);

    expect($costings)->toHaveCount(1)
        ->and($costings->first()['product_name'])->toBe('Espresso');
});

test('drink costing service only includes active products', function (): void {
    // Create an inactive product
    Product::factory()->create([
        'name' => 'Discontinued Item',
        'price' => 50.00,
        'category_id' => $this->category->id,
        'is_active' => false,
    ]);

    $service = new DrinkCostingService();
    $costings = $service->getDrinkCostings();

    $productNames = $costings->pluck('product_name')->toArray();
    expect($productNames)->not->toContain('Discontinued Item');
});
