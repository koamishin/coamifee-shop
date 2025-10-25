<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Livewire\Sidebar;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\IngredientInventory;
use App\Models\Product;
use App\Models\ProductIngredient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class SidebarTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private Product $unavailableProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestData();
    }

    public function test_sidebar_component_renders_successfully(): void
    {
        Livewire::test(Sidebar::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.pos-sidebar');
    }

    public function test_can_add_to_cart_when_product_available(): void
    {
        Livewire::test(Sidebar::class)
            ->call('addToCart', $this->product->id)
            ->assertDispatched('productSelected', $this->product->id)
            ->assertNotDispatched('insufficient-inventory');
    }

    public function test_cannot_add_to_cart_when_product_unavailable(): void
    {
        Livewire::test(Sidebar::class)
            ->call('addToCart', $this->unavailableProduct->id)
            ->assertDispatched('insufficient-inventory')
            ->assertNotDispatched('productSelected');
    }

    public function test_updates_product_availability_on_mount(): void
    {
        $component = Livewire::test(Sidebar::class);
        $component
            ->assertSet('productAvailability.'.$this->product->id.'.can_produce', true)
            ->assertSet('productAvailability.'.$this->unavailableProduct->id.'.can_produce', false);
    }

    public function test_updates_product_availability_for_all_products(): void
    {
        Livewire::test(Sidebar::class)
            ->assertSet('productAvailability.'.$this->product->id.'.can_produce', true)
            ->assertSet('productAvailability.'.$this->unavailableProduct->id.'.can_produce', false);
    }

    public function test_calculates_correct_stock_status(): void
    {
        // Create a product with low stock
        $lowStockProduct = Product::factory()->create();
        ProductIngredient::factory()->create([
            'product_id' => $lowStockProduct->id,
            'ingredient_id' => 1,
            'quantity_required' => 20,
        ]);

        // Set inventory to just enough for 5 units
        $inventory = IngredientInventory::where('ingredient_id', 1)->first();
        $inventory->update(['current_stock' => 100]);

        Livewire::test(Sidebar::class)
            ->assertSet('productAvailability.'.$lowStockProduct->id.'.stock_status', 'low_stock');
    }

    public function test_calculates_max_producible_quantity(): void
    {
        // Set inventory to allow exactly 10 units
        $inventory = IngredientInventory::where('ingredient_id', 1)->first();
        $inventory->update(['current_stock' => 200]); // 200 / 20 = 10

        $component = Livewire::test(Sidebar::class);
        $availability = $component->get('productAvailability');
        $this->assertEquals(10, $availability[$this->product->id]['max_quantity']);
    }

    public function test_filters_products_by_selected_category(): void
    {
        // Create products in different categories
        $coffeeCategory = Category::factory()->create(['name' => 'Coffee']);
        $foodCategory = Category::factory()->create(['name' => 'Food']);

        $coffeeProduct = Product::factory()->create(['category_id' => $coffeeCategory->id]);
        $foodProduct = Product::factory()->create(['category_id' => $foodCategory->id]);

        Livewire::test(Sidebar::class)
            ->set('selectedCategory', $coffeeCategory->id)
            ->assertViewHas('products', function ($products) use ($coffeeProduct, $foodProduct) {
                return $products->contains('id', $coffeeProduct->id) &&
                       ! $products->contains('id', $foodProduct->id);
            });
    }

    public function test_refreshes_inventory_on_event(): void
    {
        Livewire::test(Sidebar::class)
            ->assertSet('productAvailability.'.$this->product->id.'.can_produce', true)
            ->dispatch('refreshInventory');
            
        // After refresh, the product should still be available
        $component = Livewire::test(Sidebar::class);
        $this->assertTrue($component->get('productAvailability')[$this->product->id]['can_produce']);
    }

    public function test_loads_categories(): void
    {
        Livewire::test(Sidebar::class)
            ->assertViewHas('categories');
    }

    public function test_loads_best_sellers(): void
    {
        Livewire::test(Sidebar::class)
            ->assertViewHas('bestSellers');
    }

    public function test_loads_products(): void
    {
        Livewire::test(Sidebar::class)
            ->assertViewHas('products');
    }

    public function test_can_check_if_can_add_to_cart(): void
    {
        Livewire::test(Sidebar::class)
            ->call('addToCart', $this->product->id)
            ->assertNotDispatched('insufficient-inventory')
            ->assertDispatched('productSelected');
    }

    public function test_handles_multiple_ingredients_for_availability(): void
    {
        // Create product with multiple ingredients
        $milkIngredient = Ingredient::factory()->create(['is_trackable' => true]);
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

        Livewire::test(Sidebar::class)
            ->assertSet('productAvailability.'.$this->product->id.'.can_produce', false);
    }

    public function test_ignores_untrackable_ingredients_for_availability(): void
    {
        // Create product with untrackable ingredient
        $sugarIngredient = Ingredient::factory()->create(['is_trackable' => false]);
        ProductIngredient::factory()->create([
            'product_id' => $this->product->id,
            'ingredient_id' => $sugarIngredient->id,
            'quantity_required' => 50,
        ]);

        // Product should still be available
        Livewire::test(Sidebar::class)
            ->assertSet('productAvailability.'.$this->product->id.'.can_produce', true);
    }

    private function createTestData(): void
    {
        // Create category
        $category = Category::factory()->create(['name' => 'Coffee']);

        // Create ingredients
        $coffeeBeans = Ingredient::factory()->create([
            'name' => 'Coffee Beans',
            'is_trackable' => true,
            'unit_type' => 'grams',
        ]);

        $coffeeBeans2 = Ingredient::factory()->create([
            'name' => 'Coffee Beans (Unavailable)',
            'is_trackable' => true,
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
            'price' => 4.50,
            'category_id' => $category->id,
        ]);

        ProductIngredient::factory()->create([
            'product_id' => $this->product->id,
            'ingredient_id' => $coffeeBeans->id,
            'quantity_required' => 20,
        ]);

        // Create unavailable product
        $this->unavailableProduct = Product::factory()->create([
            'name' => 'Unavailable Latte',
            'price' => 4.50,
            'category_id' => $category->id,
        ]);

        ProductIngredient::factory()->create([
            'product_id' => $this->unavailableProduct->id,
            'ingredient_id' => $coffeeBeans2->id,
            'quantity_required' => 20,
        ]);
    }
}
