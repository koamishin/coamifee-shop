<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Livewire\Component;

final class Pos extends Component
{
    public string $search = '';

    public int $selectedCategory = 0;

    public array $cart = [];

    public float $subtotal = 0;

    public float $taxRate = 0.0;

    public float $taxAmount = 0;

    public float $total = 0;

    public string $customerName = '';

    public string $orderType = 'dine-in';

    public string $paymentMethod = 'cash';

    public string $tableNumber = '';

    public $products;

    public $categories;

    public $customers;

    public $recentOrders;

    public $bestSellers;

    public $favorites = [];

    public $todayOrders = 0;

    public $todaySales = 0.0;

    public array $addOns = [];

    public string $otherNote = '';

    public float $discountPercentage = 0;

    public bool $discountApplied = false;

    public float $discountAmount = 0;

    public string $customerSearch = '';

    public bool $showFavoritesOnly = false;

    public bool $showPaymentModal = false;

    public bool $showReceiptModal = false;

    public bool $showPaymentPanel = false;

    public $selectedProductId = null;

    public $selectedProductIds = [];

    protected $listeners = ['productSelected' => 'addToCart'];

    public function toggleSelectProduct($productId)
    {
        if (in_array($productId, $this->selectedProductIds)) {
            // Remove if already selected
            $this->selectedProductIds = array_diff($this->selectedProductIds, [$productId]);
        } else {
            // Add to selection
            $this->selectedProductIds[] = $productId;
        }
    }

    public function selectPayment(string $method): void
    {
        $this->paymentMethod = $method;
    }

    public function confirmPayment(): void
    {
        if (empty($this->cart)) {
            $this->dispatch('cart-empty', ['message' => 'Cart is empty']);

            return;
        }

        // Example: simulate saving order
        Order::create([
            'customer_name' => $this->customerName ?: 'Guest',
            'order_type' => $this->orderType,
            'payment_method' => $this->paymentMethod,
            'total' => $this->total,
        ]);

        // After payment, clear the cart and close modal
        $this->clearCart();
        $this->showPaymentModal = false;

        $this->dispatch('payment-confirmed', [
            'message' => 'Payment confirmed successfully',
        ]);
    }

    public function mount(): void
    {
        $this->calculateTotals();
        $this->loadRecentOrders();
    }

    public function render(): \Illuminate\View\View
    {
        // Load all products for dashboard stats
        $this->products = Product::with(['category', 'inventory'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Load best sellers (for now, just featured/recent products - in real app, this would be based on sales data)
        $this->bestSellers = Product::with(['category'])
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Load categories for sidebar navigation
        $this->categories = Category::where('is_active', true)->orderBy('name')->get();

        // Load recent orders for dashboard
        $this->recentOrders = Order::with('items.product')
            ->latest()
            ->take(5)
            ->get();

        // Calculate today's orders and sales
        $this->todayOrders = Order::whereDate('created_at', today())->count();
        $this->todaySales = Order::whereDate('created_at', today())->sum('total');

        // For POS functionality (when used as POS component)
        if (! empty($this->customerSearch)) {
            $this->customers = Customer::where('name', 'like', '%'.$this->customerSearch.'%')
                ->orWhere('phone', 'like', '%'.$this->customerSearch.'%')
                ->limit(5)
                ->get();
        } else {
            $this->customers = collect();
        }

        $this->calculateTotals();

        return view('livewire.pos');
    }

    // =============== CART METHODS ===============
    public function addToCart(int $productId): void
    {
        $product = Product::find($productId);
        if (! $product) {
            return;
        }

        if (! isset($this->cart[$productId])) {
            $this->cart[$productId] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => 1,
                'image' => $product->image_url,
            ];
        } else {
            $this->cart[$productId]['quantity']++;
        }

        $this->calculateTotals();
    }

    public function removeFromCart(int $productId): void
    {
        unset($this->cart[$productId]);
        $this->calculateTotals();
    }

    public function removeItem(int $productId): void
    {
        $this->removeFromCart($productId);
    }

    public function incrementQuantity(int $productId): void
    {
        if (isset($this->cart[$productId])) {
            $this->cart[$productId]['quantity']++;
            $this->calculateTotals();
        }
    }

    public function decrementQuantity(int $productId): void
    {
        if (isset($this->cart[$productId])) {
            if ($this->cart[$productId]['quantity'] > 1) {
                $this->cart[$productId]['quantity']--;
            } else {
                unset($this->cart[$productId]);
            }
            $this->calculateTotals();
        }
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->addOns = [];
        $this->otherNote = '';
        $this->discountPercentage = 0;
        $this->discountApplied = false;
        $this->discountAmount = 0;
        $this->calculateTotals();
    }

    public function addAddOn(): void
    {
        $this->addOns[] = [
            'label' => '',
            'amount' => 0.0,
        ];
    }

    public function removeAddOn(int $index): void
    {
        if (isset($this->addOns[$index])) {
            unset($this->addOns[$index]);
            $this->addOns = array_values($this->addOns); // Reindex array
            $this->calculateTotals();
        }
    }

    public function getCartItemCount(): int
    {
        return array_sum(array_column($this->cart, 'quantity'));
    }

    public function toggleFavorite(int $productId): void
    {
        if (in_array($productId, $this->favorites)) {
            $this->favorites = array_diff($this->favorites, [$productId]);
        } else {
            $this->favorites[] = $productId;
        }
    }

    public function selectCustomer(int $customerId): void
    {
        $customer = Customer::find($customerId);
        if ($customer) {
            $this->customerName = $customer->name;
            $this->customerSearch = '';
        }
    }

    public function holdOrder(): void
    {
        $this->dispatch('order-held', ['message' => 'Order held successfully']);
    }

    public function loadOrder(int $orderId): void
    {
        $order = Order::with('items.product')->find($orderId);
        if (! $order) {
            return;
        }

        $this->cart = [];
        foreach ($order->items as $item) {
            $this->cart[$item->product_id] = [
                'id' => $item->product_id,
                'name' => $item->product->name,
                'price' => $item->price,
                'quantity' => $item->quantity,
                'image' => $item->product->image_url,
            ];
        }

        $this->customerName = $order->customer_name ?? '';
        $this->orderType = $order->order_type ?? 'dine-in';
        $this->calculateTotals();
    }

    public function applyDiscount(): void
    {
        if ($this->discountPercentage > 0 && $this->discountPercentage <= 100) {
            $this->discountApplied = true;
            $this->discountAmount = $this->subtotal * ($this->discountPercentage / 100);
        } else {
            $this->discountApplied = false;
            $this->discountAmount = 0;
        }
        $this->calculateTotals();
    }

    public function removeDiscount(): void
    {
        $this->discountPercentage = 0;
        $this->discountApplied = false;
        $this->discountAmount = 0;
        $this->calculateTotals();
    }

    public function printReport(): void
    {
        $this->dispatch('print-report', [
            'message' => 'Generating sales report...',
            'data' => [
                'todaySales' => $this->todaySales,
                'todayOrders' => $this->todayOrders,
                'products' => $this->products->count(),
            ],
        ]);
    }

    public function clearAllOrders(): void
    {
        // This would clear all orders from database - be careful with this!
        $this->dispatch('clear-all-orders', [
            'message' => 'This will clear all order history. Are you sure?',
            'type' => 'warning',
        ]);
    }

    public function showSalesSummary(): void
    {
        $this->dispatch('show-sales-summary', [
            'data' => [
                'todaySales' => $this->todaySales,
                'todayOrders' => $this->todayOrders,
                'avgOrder' => $this->todayOrders > 0 ? $this->todaySales / $this->todayOrders : 0,
                'totalProducts' => $this->products->count(),
            ],
        ]);
    }

    public function openSettings(): void
    {
        $this->dispatch('open-settings', [
            'message' => 'Settings panel opened',
        ]);
    }

    public function processPayment(): void
    {
        if (empty($this->cart)) {
            $this->dispatch('cart-empty', ['message' => 'Cart is empty']);

            return;
        }

        $this->dispatch('payment-processed', [
            'message' => 'Payment processed successfully',
            'total' => $this->total,
        ]);

        $this->clearCart();
        $this->loadRecentOrders();
    }

    private function loadRecentOrders(): void
    {
        $this->recentOrders = Order::latest()->take(5)->get();
    }

    private function calculateTotals(): void
    {
        $subtotal = 0.0;

        foreach ($this->cart as $item) {
            $subtotal += ($item['price'] * $item['quantity']);
        }

        // Add add-ons to subtotal
        foreach ($this->addOns as $addOn) {
            $subtotal += (float) ($addOn['amount'] ?? 0);
        }

        $this->subtotal = $subtotal;
        $this->taxAmount = 0;
        $this->total = $this->subtotal + $this->taxAmount;

        if ($this->discountApplied) {
            $this->total -= $this->discountAmount;
        }
    }
}
