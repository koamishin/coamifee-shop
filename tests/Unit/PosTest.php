<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Livewire\Pos;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\IngredientInventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductIngredient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class PosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->createTestData();
    }

    public function test_pos_component_renders_successfully(): void
    {
        Livewire::test(Pos::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.pos');
    }

    public function test_can_add_product_to_cart(): void
    {
        Livewire::test(Pos::class)
            ->call('addToCart', $this->product->id)
            ->assertDispatched('productSelected', $this->product->id)
            ->assertSet('cart.'.$this->product->id.'.quantity', 1)
            ->assertSet('cart.'.$this->product->id.'.name', $this->product->name)
            ->assertSet('cart.'.$this->product->id.'.price', $this->product->price);
    }

    public function test_cannot_add_product_with_insufficient_inventory(): void
    {
        // Reduce inventory to insufficient levels
        $inventory = IngredientInventory::where('ingredient_id', 1)->first();
        $inventory->update(['current_stock' => 10]); // Less than required 20g

        Livewire::test(Pos::class)
            ->call('addToCart', $this->product->id)
            ->assertDispatched('insufficient-inventory')
            ->assertSet('cart', []);
    }

    public function test_can_increment_cart_item_quantity(): void
    {
        Livewire::test(Pos::class)
            ->call('addToCart', $this->product->id)
            ->call('incrementQuantity', $this->product->id)
            ->assertSet('cart.'.$this->product->id.'.quantity', 2);
    }

    public function test_cannot_increment_beyond_available_inventory(): void
    {
        // Set inventory to only allow 1 unit
        $inventory = IngredientInventory::where('ingredient_id', 1)->first();
        $inventory->update(['current_stock' => 20]); // Exactly enough for 1

        Livewire::test(Pos::class)
            ->call('addToCart', $this->product->id)
            ->call('incrementQuantity', $this->product->id)
            ->assertDispatched('insufficient-inventory')
            ->assertSet('cart.'.$this->product->id.'.quantity', 1);
    }

    public function test_can_decrement_cart_item_quantity(): void
    {
        Livewire::test(Pos::class)
            ->call('addToCart', $this->product->id)
            ->call('incrementQuantity', $this->product->id)
            ->call('decrementQuantity', $this->product->id)
            ->assertSet('cart.'.$this->product->id.'.quantity', 1);
    }

    public function test_decrement_to_zero_removes_item(): void
    {
        Livewire::test(Pos::class)
            ->call('addToCart', $this->product->id)
            ->call('decrementQuantity', $this->product->id)
            ->assertSet('cart.'.$this->product->id, null);
    }

    public function test_can_remove_item_from_cart(): void
    {
        Livewire::test(Pos::class)
            ->call('addToCart', $this->product->id)
            ->call('removeFromCart', $this->product->id)
            ->assertSet('cart.'.$this->product->id, null);
    }

    public function test_can_clear_cart(): void
    {
        Livewire::test(Pos::class)
            ->call('addToCart', $this->product->id)
            ->call('clearCart')
            ->assertSet('cart', []);
    }

    public function test_calculates_totals_correctly(): void
    {
        Livewire::test(Pos::class)
            ->call('addToCart', $this->product->id)
            ->call('incrementQuantity', $this->product->id)
            ->assertSet('subtotal', 9.00) // 2 * 4.50
            ->assertSet('total', 9.00);
    }

    public function test_can_apply_discount(): void
    {
        Livewire::test(Pos::class)
            ->call('addToCart', $this->product->id)
            ->set('discountPercentage', 10)
            ->call('applyDiscount')
            ->assertSet('discountApplied', true)
            ->assertSet('discountAmount', 0.45) // 10% of 4.50
            ->assertSet('total', 4.05);
    }

    public function test_can_quick_add_product_with_size(): void
    {
        Livewire::test(Pos::class)
            ->call('quickAddProduct', $this->product->id, 'large', 'hot')
            ->assertSet('cart.'.$this->product->id.'_large_hot.size', 'large')
            ->assertSet('cart.'.$this->product->id.'_large_hot.temperature', 'hot')
            ->assertSet('cart.'.$this->product->id.'_large_hot.price', 5.625); // 4.50 * 1.25
    }

    public function test_can_apply_customer_discount_code(): void
    {
        Livewire::test(Pos::class)
            ->call('addToCart', $this->product->id)
            ->call('applyCustomerDiscount', 'COFFEE10')
            ->assertDispatched('discount-applied')
            ->assertSet('discountPercentage', 10)
            ->assertSet('total', 4.05);
    }

    public function test_invalid_discount_code_is_rejected(): void
    {
        Livewire::test(Pos::class)
            ->call('addToCart', $this->product->id)
            ->call('applyCustomerDiscount', 'INVALID')
            ->assertDispatched('discount-invalid');
    }

    public function test_can_duplicate_existing_order(): void
    {
        // Create a completed order
        $order = Order::factory()->create([
            'customer_name' => 'John Doe',
            'total' => 9.00,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'price' => 4.50,
        ]);

        Livewire::test(Pos::class)
            ->call('duplicateOrder', $order->id)
            ->assertDispatched('order-duplicated')
            ->assertSet('customerName', 'John Doe')
            ->assertSet('cart.'.$this->product->id.'.quantity', 2);
    }

    public function test_cannot_duplicate_order_with_insufficient_inventory(): void
    {
        // Create order with 3 units
        $order = Order::factory()->create();
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'quantity' => 3,
        ]);

        // Reduce inventory to insufficient for 3 units
        $inventory = IngredientInventory::where('ingredient_id', 1)->first();
        $inventory->update(['current_stock' => 40]); // Only enough for 2 units

        Livewire::test(Pos::class)
            ->call('duplicateOrder', $order->id)
            ->assertDispatched('insufficient-inventory');
    }

    public function test_can_generate_receipt(): void
    {
        Livewire::test(Pos::class)
            ->call('addToCart', $this->product->id)
            ->set('customerName', 'Test Customer')
            ->call('generateReceipt')
            ->assertDispatched('receipt-generated');
    }

    public function test_gets_cart_item_count(): void
    {
        $result = Livewire::test(Pos::class)
            ->call('addToCart', $this->product->id)
            ->call('incrementQuantity', $this->product->id)
            ->call('addToCart', $this->product->id) // Same product again
        ;
        
        $cart = $result->get('cart');
        // Since we added twice and incremented once, should be 3
        $this->assertEquals(3, $cart[$this->product->id]['quantity']);
    }

    public function test_process_payment_with_empty_cart_fails(): void
    {
        Livewire::test(Pos::class)
            ->call('processPayment')
            ->assertDispatched('cart-empty');
    }

    public function test_can_customize_cart_item(): void
    {
        Livewire::test(Pos::class)
            ->call('addToCart', $this->product->id)
            ->call('customizeCartItem', $this->product->id, [
                'milk' => 'oat',
                'extra_shots' => 1,
            ])
            ->assertSet('cart.'.$this->product->id.'.customizations', [
                'milk' => 'oat',
                'extra_shots' => 1,
            ]);
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

        $milk = Ingredient::factory()->create([
            'name' => 'Milk',
            'is_trackable' => true,
            'unit_type' => 'ml',
        ]);

        // Create ingredient inventory
        IngredientInventory::factory()->create([
            'ingredient_id' => $coffeeBeans->id,
            'current_stock' => 1000,
            'min_stock_level' => 100,
        ]);

        IngredientInventory::factory()->create([
            'ingredient_id' => $milk->id,
            'current_stock' => 5000,
            'min_stock_level' => 500,
        ]);

        // Create product
        $this->product = Product::factory()->create([
            'name' => 'Latte',
            'price' => 4.50,
            'category_id' => $category->id,
        ]);

        // Create product ingredients
        ProductIngredient::factory()->create([
            'product_id' => $this->product->id,
            'ingredient_id' => $coffeeBeans->id,
            'quantity_required' => 20,
        ]);

        ProductIngredient::factory()->create([
            'product_id' => $this->product->id,
            'ingredient_id' => $milk->id,
            'quantity_required' => 200,
        ]);
    }
}
