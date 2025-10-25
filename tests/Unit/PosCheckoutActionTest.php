<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Actions\PosCheckoutAction;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Ingredient;
use App\Models\IngredientInventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductIngredient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PosCheckoutActionTest extends TestCase
{
    use RefreshDatabase;

    private PosCheckoutAction $action;
    private Product $product;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->action = app(PosCheckoutAction::class);
        $this->createTestData();
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
        
        // Create customer
        $this->customer = Customer::factory()->create(['name' => 'John Doe']);
        
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

    public function test_can_checkout_successfully(): void
    {
        $cart = [
            $this->product->id => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'price' => $this->product->price,
                'quantity' => 2,
                'image' => $this->product->image_url,
            ],
        ];

        $orderData = [
            'customer_name' => 'John Doe',
            'order_type' => 'dine-in',
            'payment_method' => 'cash',
            'table_number' => 'A1',
            'total' => 9.00,
            'notes' => 'Extra hot',
        ];

        $result = $this->action->execute($cart, $orderData);

        $this->assertTrue($result['success']);
        $this->assertEquals('Order completed successfully', $result['message']);
        $this->assertArrayHasKey('order_id', $result);
        $this->assertArrayHasKey('order_number', $result);

        // Check order was created
        $order = Order::find($result['order_id']);
        $this->assertNotNull($order);
        $this->assertEquals('John Doe', $order->customer_name);
        $this->assertEquals('dine-in', $order->order_type);
        $this->assertEquals('cash', $order->payment_method);
        $this->assertEquals('A1', $order->table_number);
        $this->assertEquals(9.00, $order->total);
        $this->assertEquals('completed', $order->status);
        $this->assertEquals('Extra hot', $order->notes);

        // Check order items were created
        $this->assertCount(1, $order->items);
        $orderItem = $order->items->first();
        $this->assertEquals($this->product->id, $orderItem->product_id);
        $this->assertEquals(2, $orderItem->quantity);
        $this->assertEquals(4.50, $orderItem->price);
    }

    public function test_checkout_fails_with_empty_cart(): void
    {
        $cart = [];
        $orderData = [
            'customer_name' => 'John Doe',
            'total' => 0,
        ];

        $result = $this->action->execute($cart, $orderData);

        $this->assertFalse($result['success']);
        $this->assertEquals('Cart is empty', $result['message']);
    }

    public function test_checkout_fails_with_nonexistent_product(): void
    {
        $cart = [
            999 => [
                'id' => 999,
                'name' => 'Nonexistent Product',
                'price' => 5.00,
                'quantity' => 1,
            ],
        ];

        $orderData = [
            'customer_name' => 'John Doe',
            'total' => 5.00,
        ];

        $result = $this->action->execute($cart, $orderData);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', $result['message']);
    }

    public function test_checkout_with_insufficient_inventory_fails(): void
    {
        // Reduce inventory to insufficient levels
        $inventory = IngredientInventory::where('ingredient_id', 1)->first();
        $inventory->update(['current_stock' => 10]); // Less than required 20g

        $cart = [
            $this->product->id => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'price' => $this->product->price,
                'quantity' => 1,
            ],
        ];

        $orderData = [
            'customer_name' => 'John Doe',
            'total' => 4.50,
        ];

        $result = $this->action->execute($cart, $orderData);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Insufficient ingredients', $result['message']);
    }

    public function test_checkout_sets_guest_customer_when_no_name_provided(): void
    {
        $cart = [
            $this->product->id => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'price' => $this->product->price,
                'quantity' => 1,
            ],
        ];

        $orderData = [
            'customer_name' => null,
            'total' => 4.50,
        ];

        $result = $this->action->execute($cart, $orderData);

        $this->assertTrue($result['success']);

        $order = Order::find($result['order_id']);
        $this->assertEquals('Guest', $order->customer_name);
        $this->assertNull($order->customer_id);
    }

    public function test_checkout_links_to_existing_customer(): void
    {
        $cart = [
            $this->product->id => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'price' => $this->product->price,
                'quantity' => 1,
            ],
        ];

        $orderData = [
            'customer_name' => $this->customer->name,
            'total' => 4.50,
        ];

        $result = $this->action->execute($cart, $orderData);

        $this->assertTrue($result['success']);

        $order = Order::find($result['order_id']);
        $this->assertEquals($this->customer->id, $order->customer_id);
    }

    public function test_checkout_with_default_values(): void
    {
        $cart = [
            $this->product->id => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'price' => $this->product->price,
                'quantity' => 1,
            ],
        ];

        $orderData = ['total' => 4.50]; // Only total is required

        $result = $this->action->execute($cart, $orderData);

        $this->assertTrue($result['success']);

        $order = Order::find($result['order_id']);
        $this->assertEquals('Guest', $order->customer_name);
        $this->assertEquals('dine-in', $order->order_type);
        $this->assertEquals('cash', $order->payment_method);
        $this->assertNull($order->table_number);
        $this->assertNull($order->notes);
    }

    public function test_checkout_creates_multiple_order_items(): void
    {
        // Create second product
        $secondProduct = Product::factory()->create(['name' => 'Cappuccino', 'price' => 5.00]);

        $cart = [
            $this->product->id => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'price' => $this->product->price,
                'quantity' => 2,
            ],
            $secondProduct->id => [
                'id' => $secondProduct->id,
                'name' => $secondProduct->name,
                'price' => $secondProduct->price,
                'quantity' => 1,
            ],
        ];

        $orderData = [
            'customer_name' => 'John Doe',
            'total' => 14.00, // 2*4.50 + 1*5.00
        ];

        $result = $this->action->execute($cart, $orderData);

        $this->assertTrue($result['success']);

        $order = Order::find($result['order_id']);
        $this->assertCount(2, $order->items);

        $firstItem = $order->items->where('product_id', $this->product->id)->first();
        $this->assertEquals(2, $firstItem->quantity);

        $secondItem = $order->items->where('product_id', $secondProduct->id)->first();
        $this->assertEquals(1, $secondItem->quantity);
    }

    public function test_calculates_order_total_correctly(): void
    {
        $cart = [
            $this->product->id => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'price' => $this->product->price,
                'quantity' => 3,
            ],
        ];

        $result = $this->action->calculateOrderTotal($cart, 10.0);

        $expectedSubtotal = 4.50 * 3; // 13.50
        $expectedTax = 13.50 * 0.10; // 1.35
        $expectedTotal = 13.50 + 1.35; // 14.85

        $this->assertEquals($expectedSubtotal, $result['subtotal']);
        $this->assertEquals($expectedTax, $result['tax_amount']);
        $this->assertEquals($expectedTotal, $result['total']);
    }

    public function test_handles_cart_item_with_notes(): void
    {
        $cart = [
            $this->product->id => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'price' => $this->product->price,
                'quantity' => 1,
                'notes' => 'Extra hot, no foam',
            ],
        ];

        $orderData = [
            'customer_name' => 'John Doe',
            'total' => 4.50,
        ];

        $result = $this->action->execute($cart, $orderData);

        $this->assertTrue($result['success']);

        $order = Order::find($result['order_id']);
        $orderItem = $order->items->first();
        $this->assertEquals('Extra hot, no foam', $orderItem->notes);
    }

    private function signIn(): void
    {
        $this->actingAs(\App\Models\User::factory()->create());
    }
}
