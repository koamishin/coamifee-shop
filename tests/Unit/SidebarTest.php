<?php

declare(strict_types=1);

use App\Livewire\Sidebar;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\IngredientInventory;
use App\Models\Product;
use App\Models\ProductIngredient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('sidebar component renders successfully', function (): void {
    Livewire::test(Sidebar::class)
        ->assertStatus(200)
        ->assertViewIs('livewire.pos-sidebar');
});

test('can add to cart when product available', function (): void {
    Livewire::test(Sidebar::class)
        ->call('addToCart', $this->product->id)
        ->assertNotDispatched('insufficient-inventory');
});

test('cannot add to cart when product unavailable', function (): void {
    Livewire::test(Sidebar::class)
        ->call('addToCart', $this->unavailableProduct->id)
        ->assertDispatched('insufficient-inventory')
        ->assertNotDispatched('productSelected');
});

test('updates product availability on mount', function (): void {
    $component = Livewire::test(Sidebar::class);
    $component
        ->assertSet(
            'productAvailability.'.$this->product->id.'.can_produce',
            true,
        )
        ->assertSet(
            'productAvailability.'.
                $this->unavailableProduct->id.
                '.can_produce',
            false,
        );
});

test('updates product availability for all products', function (): void {
    $component = Livewire::test(Sidebar::class);

    // Test that all products have availability data
    $availability = $component->get('productAvailability');

    // Availability should not be empty
    expect($availability)->not->toBeEmpty();
    
    // Check that products have the expected structure
    foreach ($availability as $productId => $data) {
        expect($data)->toHaveKeys(['can_produce', 'max_quantity', 'stock_status']);
    }

    // Should have availability for both test products
    expect($availability)->toHaveKey($this->product->id);
    expect($availability)->toHaveKey($this->unavailableProduct->id);

    // Check the values are correct
    expect($availability[$this->product->id]['can_produce'])->toBeTrue();
    expect($availability[$this->unavailableProduct->id]['can_produce'])->toBeFalse();
});

test('calculates correct stock status', function (): void {
    // Create a product with low stock
    $lowStockProduct = Product::factory()->create();
    ProductIngredient::factory()->create([
        'product_id' => $lowStockProduct->id,
        'ingredient_id' => 1,
        'quantity_required' => 20,
    ]);

    // Set inventory to just enough for 5 units
    $inventory = IngredientInventory::query()->where('ingredient_id', 1)->first();
    $inventory->update(['current_stock' => 100]);

    Livewire::test(Sidebar::class)->assertSet(
        'productAvailability.'.$lowStockProduct->id.'.stock_status',
        'low_stock',
    );
});

test('calculates max producible quantity', function (): void {
    // Set inventory to allow exactly 10 units
    $inventory = IngredientInventory::query()->where('ingredient_id', 1)->first();
    $inventory->update(['current_stock' => 200]); // 200 / 20 = 10

    $component = Livewire::test(Sidebar::class);
    $availability = $component->get('productAvailability');
    
    // Find the correct product in availability array
    expect($availability)->toHaveKey($this->product->id);
    $productAvailability = $availability[$this->product->id];
    expect($productAvailability['max_quantity'])->toBe(10);
});

test('filters products by selected category', function (): void {
    // Create products in different categories
    $coffeeCategory = Category::factory()->create(['name' => 'Coffee']);
    $foodCategory = Category::factory()->create(['name' => 'Food']);

    $coffeeProduct = Product::factory()->create([
        'category_id' => $coffeeCategory->id,
    ]);
    $foodProduct = Product::factory()->create([
        'category_id' => $foodCategory->id,
    ]);

    $component = Livewire::test(Sidebar::class);
    
    // Select the coffee category
    $component->call('selectCategory', $coffeeCategory->id);
    
    // Get the filtered products
    $products = $component->viewData('products');
    
    // Verify filtering works
    expect($products->contains('id', $coffeeProduct->id))->toBeTrue();
    expect($products->contains('id', $foodProduct->id))->toBeFalse();
});

test('refreshes inventory on event', function (): void {
    $component = Livewire::test(Sidebar::class)
        ->assertSet(
            'productAvailability.'.$this->product->id.'.can_produce',
            true,
        )
        ->dispatch('refreshInventory');

    // After refresh, product should still be available
    $component->assertSet(
        'productAvailability.'.$this->product->id.'.can_produce',
        true,
    );
});

test('loads categories', function (): void {
    Livewire::test(Sidebar::class)->assertViewHas('categories');
});

test('loads best sellers', function (): void {
    Livewire::test(Sidebar::class)->assertViewHas('bestSellers');
});

test('loads products', function (): void {
    Livewire::test(Sidebar::class)->assertViewHas('products');
});

test('can check if can add to cart', function (): void {
    Livewire::test(Sidebar::class)
        ->call('addToCart', $this->product->id)
        ->assertNotDispatched('insufficient-inventory');
});

test('handles multiple ingredients for availability', function (): void {
    // Create product with multiple ingredients
    $milkIngredient = Ingredient::factory()->create();
    ProductIngredient::factory()->create([
        'product_id' => $this->product->id,
        'ingredient_id' => $milkIngredient->id,
        'quantity_required' => 100,
    ]);

    // Set one ingredient to 0, product should be unavailable
    IngredientInventory::factory()->create([
        'ingredient_id' => $milkIngredient->id,
        'current_stock' => 0,
    ]);

    Livewire::test(Sidebar::class)->assertSet(
        'productAvailability.'.$this->product->id.'.can_produce',
        false,
    );
});

test('handles ingredients without inventory correctly', function (): void {
    // Create an ingredient without inventory
    $sugarIngredient = Ingredient::factory()->create([
        'name' => 'Sugar',
        'unit_type' => 'grams',
    ]);
    ProductIngredient::factory()->create([
        'product_id' => $this->product->id,
        'ingredient_id' => $sugarIngredient->id,
        'quantity_required' => 50,
    ]);

    // Product should be unavailable because sugar has no inventory
    Livewire::test(Sidebar::class)->assertSet(
        'productAvailability.'.$this->product->id.'.can_produce',
        false,
    );
});

// Helper function to create test data
beforeEach(function (): void {
    // Create category
    $category = Category::factory()->create(['name' => 'Coffee']);

    // Create ingredients
    $coffeeBeans = Ingredient::factory()->create([
        'name' => 'Coffee Beans',
        'unit_type' => 'grams',
    ]);

    $coffeeBeans2 = Ingredient::factory()->create([
        'name' => 'Coffee Beans (Unavailable)',
        'unit_type' => 'grams',
    ]);

    // Create ingredient inventory for available product
    IngredientInventory::factory()->create([
        'ingredient_id' => $coffeeBeans->id,
        'current_stock' => 1000,
        'min_stock_level' => 100,
    ]);

    IngredientInventory::factory()->create([
        'ingredient_id' => $coffeeBeans2->id,
        'current_stock' => 0, // No stock for unavailable product
        'min_stock_level' => 100,
    ]);

    // Create available product
    $this->product = Product::factory()->create([
        'name' => 'Available Latte',
        'price' => 4.5,
        'category_id' => $category->id,
        'is_active' => true,
    ]);

    ProductIngredient::factory()->create([
        'product_id' => $this->product->id,
        'ingredient_id' => $coffeeBeans->id,
        'quantity_required' => 20,
    ]);

    // Create unavailable product
    $this->unavailableProduct = Product::factory()->create([
        'name' => 'Unavailable Latte',
        'price' => 4.5,
        'category_id' => $category->id,
        'is_active' => true,
    ]);

    ProductIngredient::factory()->create([
        'product_id' => $this->unavailableProduct->id,
        'ingredient_id' => $coffeeBeans2->id,
        'quantity_required' => 20,
    ]);
});
