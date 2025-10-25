<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Livewire\Pos;
use Livewire\Livewire;
use Tests\TestCase;

final class PosBasicTest extends TestCase
{
    use RefreshDatabase;
    public function test_pos_component_renders(): void
    {
        Livewire::test(Pos::class)
            ->assertStatus(200);
    }

    public function test_cart_can_be_cleared(): void
    {
        $component = Livewire::test(Pos::class);
        $component->set('cart', [
            '1' => [
                'id' => 1,
                'name' => 'Test',
                'price' => 5.00,
                'quantity' => 1,
                'image' => null,
            ]
        ]);
        $component->call('clearCart');
        $component->assertSet('cart', []);
    }

    public function test_cart_item_count_works(): void
    {
        $pos = new Pos();
        $pos->cart = [
            '1' => ['quantity' => 2],
            '2' => ['quantity' => 1],
        ];
        
        $result = $pos->getCartItemCount();
        $this->assertEquals(3, $result);
    }

    // Note: calculateTotals is private, so we can't test it directly
    // but we can test the effects when it's called via other methods

    public function test_discount_can_be_applied(): void
    {
        $component = Livewire::test(Pos::class);
        $component->set('cart', [
            '1' => [
                'id' => 1,
                'name' => 'Test Product',
                'price' => 10.00,
                'quantity' => 1,
                'image' => null,
            ],
        ]);
        $component->set('discountPercentage', 10);
        $component->call('applyDiscount');
        
        $component->assertSet('discountApplied', true);
        $component->assertSet('discountAmount', 1.00);
        $component->assertSet('total', 9.00);
    }

    public function test_discount_can_be_removed(): void
    {
        $pos = new Pos();
        $pos->cart = [
            '1' => ['price' => 10.00, 'quantity' => 1],
        ];
        $pos->discountPercentage = 10;
        $pos->discountApplied = true;
        $pos->discountAmount = 1.00;
        
        $pos->removeDiscount();
        
        $this->assertEquals(0, $pos->discountPercentage);
        $this->assertFalse($pos->discountApplied);
        $this->assertEquals(0, $pos->discountAmount);
    }

    public function test_customer_discount_code_valid(): void
    {
        $component = Livewire::test(Pos::class);
        $component->set('cart', [
            '1' => [
                'id' => 1,
                'name' => 'Test Product',
                'price' => 10.00,
                'quantity' => 1,
                'image' => null,
            ],
        ]);
        
        $component->call('applyCustomerDiscount', 'COFFEE10');
        
        $component->assertSet('discountPercentage', 10);
    }

    public function test_customer_discount_code_invalid(): void
    {
        $component = Livewire::test(Pos::class);
        
        $component->call('applyCustomerDiscount', 'INVALID');
        
        $component->assertDispatched('discount-invalid');
    }

    public function test_receipt_generation(): void
    {
        $component = Livewire::test(Pos::class);
        $component->set('cart', [
            '1' => [
                'id' => 1,
                'name' => 'Latte',
                'price' => 4.50,
                'quantity' => 1,
                'image' => null,
            ],
        ]);
        $component->set('customerName', 'Test Customer');
        $component->set('orderType', 'dine-in');
        $component->set('paymentMethod', 'cash');
        $component->set('subtotal', 4.50);
        $component->set('total', 4.50);
        
        $component->call('generateReceipt');
        
        $component->assertDispatched('receipt-generated');
    }

    public function test_process_payment_with_empty_cart(): void
    {
        $component = Livewire::test(Pos::class);
        $component->set('cart', []);
        
        $component->call('processPayment');
        
        $component->assertDispatched('cart-empty');
    }
}
