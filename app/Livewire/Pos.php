<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\PosCheckoutAction;
use App\Actions\ProcessOrderAction;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Services\InventoryService;
use App\Services\OrderProcessingService;
use App\Services\ReportingService;
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

    // Coffee shop specific features
    public array $quickAddItems = [];

    public array $sizeOptions = ['small', 'medium', 'large'];

    public array $temperatureOptions = ['hot', 'iced', 'blended'];

    public array $milkOptions = ['whole', 'skim', 'oat', 'almond', 'soy'];

    public array $customizations = [];

    public $selectedProductId = null;

    public $selectedProductIds = [];

    public array $lowStockAlerts = [];

    public array $productAvailability = [];

    protected $listeners = ['productSelected' => 'addToCart'];

    public function boot(
        OrderProcessingService $orderProcessingService,
        InventoryService $inventoryService,
        ProcessOrderAction $processOrderAction,
        ReportingService $reportingService,
        PosCheckoutAction $posCheckoutAction
    ): void {
        $this->orderProcessingService = $orderProcessingService;
        $this->inventoryService = $inventoryService;
        $this->processOrderAction = $processOrderAction;
        $this->reportingService = $reportingService;
        $this->posCheckoutAction = $posCheckoutAction;
    }

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

        // Prepare order data
        $orderData = [
            'customer_name' => $this->customerName ?: 'Guest',
            'order_type' => $this->orderType,
            'payment_method' => $this->paymentMethod,
            'table_number' => $this->tableNumber,
            'total' => $this->total,
            'notes' => $this->otherNote,
        ];

        // Process checkout
        $result = $this->posCheckoutAction->execute($this->cart, $orderData);

        if ($result['success']) {
            $this->clearCart();
            $this->showPaymentModal = false;

            $this->dispatch('payment-confirmed', [
                'message' => $result['message'],
                'order_id' => $result['order_id'],
                'order_number' => $result['order_number'],
            ]);

            // Refresh data
            $this->loadRecentOrders();
            $this->checkLowStock();
            $this->updateProductAvailability();
        } else {
            $this->dispatch('order-failed', ['message' => $result['message']]);
        }
    }

    public function mount(): void
    {
        // Initialize products before using them
        $this->products = Product::with(['category', 'ingredients.ingredient'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $this->calculateTotals();
        $this->loadRecentOrders();
        $this->checkLowStock();
        $this->updateProductAvailability();
        $this->loadQuickAddItems();
    }

    public function render(): \Illuminate\View\View
    {
        // Load products with availability info
        $this->products = Product::with(['category', 'ingredients.ingredient'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Load best sellers based on actual metrics data
        $this->bestSellers = $this->reportingService->getTopProducts(5, 'daily', 7)
            ->map(function ($metric) {
                return $metric->product;
            });

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

    // =============== COFFEE SHOP SPECIFIC METHODS ===============
    public function loadQuickAddItems(): void
    {
        // Load popular coffee items for quick access
        $this->quickAddItems = Product::where('is_active', true)
            ->whereHas('category', function ($query) {
                $query->whereIn('name', ['Coffee', 'Espresso', 'Latte']);
            })
            ->orderBy('name')
            ->take(8)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'image' => $product->image_url,
                    'category' => $product->category->name,
                    'can_produce' => $this->inventoryService->canProduceProduct($product->id, 1),
                ];
            })
            ->toArray();
    }

    public function quickAddProduct(int $productId, string $size = 'medium', string $temperature = 'hot'): void
    {
        if (! $this->inventoryService->canProduceProduct($productId, 1)) {
            $this->dispatch('insufficient-inventory', [
                'message' => 'Cannot add product: Insufficient ingredients',
                'product_id' => $productId,
            ]);

            return;
        }

        $product = Product::find($productId);
        if (! $product) {
            return;
        }

        $cartItemId = $productId.'_'.$size.'_'.$temperature;

        // Calculate price based on size
        $priceMultiplier = match ($size) {
            'small' => 0.85,
            'large' => 1.25,
            default => 1.0,
        };

        $adjustedPrice = $product->price * $priceMultiplier;

        if (! isset($this->cart[$cartItemId])) {
            $this->cart[$cartItemId] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $adjustedPrice,
                'quantity' => 1,
                'image' => $product->image_url,
                'size' => $size,
                'temperature' => $temperature,
                'customizations' => [],
            ];
        } else {
            if (! $this->inventoryService->canProduceProduct($productId, $this->cart[$cartItemId]['quantity'] + 1)) {
                $this->dispatch('insufficient-inventory', [
                    'message' => 'Cannot add more: Insufficient ingredients',
                    'product_id' => $productId,
                ]);

                return;
            }
            $this->cart[$cartItemId]['quantity']++;
        }

        $this->calculateTotals();
        $this->updateProductAvailability();
    }

    public function addCustomization(string $key, $value): void
    {
        $this->customizations[$key] = $value;
    }

    public function removeCustomization(string $key): void
    {
        unset($this->customizations[$key]);
    }

    public function customizeCartItem(string $cartItemId, array $customizations): void
    {
        if (isset($this->cart[$cartItemId])) {
            $this->cart[$cartItemId]['customizations'] = $customizations;

            // Adjust price for customizations
            $product = Product::find($this->cart[$cartItemId]['id']);
            if ($product) {
                $basePrice = $product->price;
                $customizationPrice = $this->calculateCustomizationPrice($customizations);
                $this->cart[$cartItemId]['price'] = $basePrice + $customizationPrice;
            }

            $this->calculateTotals();
        }
    }

    public function duplicateOrder(int $orderId): void
    {
        $order = Order::with('items.product')->find($orderId);
        if (! $order) {
            return;
        }

        $this->cart = [];
        foreach ($order->items as $item) {
            $cartItemId = $item->product_id;

            // Check if we can produce this item
            if (! $this->inventoryService->canProduceProduct($item->product_id, $item->quantity)) {
                $this->dispatch('insufficient-inventory', [
                    'message' => "Cannot duplicate order: Insufficient ingredients for {$item->product->name}",
                    'product_name' => $item->product->name,
                ]);

                continue;
            }

            $this->cart[$cartItemId] = [
                'id' => $item->product_id,
                'name' => $item->product->name,
                'price' => $item->price,
                'quantity' => $item->quantity,
                'image' => $item->product->image_url,
                'customizations' => [],
            ];
        }

        $this->customerName = $order->customer_name ?? '';
        $this->orderType = $order->order_type ?? 'dine-in';
        $this->tableNumber = $order->table_number ?? '';
        $this->calculateTotals();
        $this->updateProductAvailability();

        $this->dispatch('order-duplicated', [
            'message' => 'Order duplicated successfully',
            'order_id' => $orderId,
        ]);
    }

    public function applyCustomerDiscount(string $discountCode): void
    {
        // Simple discount logic - in real app, this would check against a database
        $discounts = [
            'COFFEE10' => 10,
            'COFFEE15' => 15,
            'WELCOME' => 5,
        ];

        if (isset($discounts[$discountCode])) {
            $this->discountPercentage = $discounts[$discountCode];
            $this->applyDiscount();

            $this->dispatch('discount-applied', [
                'message' => "Discount code {$discountCode} applied: {$discounts[$discountCode]}% off",
                'percentage' => $discounts[$discountCode],
            ]);
        } else {
            $this->dispatch('discount-invalid', [
                'message' => 'Invalid discount code',
                'code' => $discountCode,
            ]);
        }
    }

    public function generateReceipt(): void
    {
        $receiptData = [
            'order_number' => 'POS-'.time(),
            'date' => now()->format('M d, Y H:i'),
            'customer_name' => $this->customerName ?: 'Guest',
            'order_type' => $this->orderType,
            'table_number' => $this->tableNumber,
            'items' => [],
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->taxAmount,
            'discount_amount' => $this->discountAmount,
            'total' => $this->total,
            'payment_method' => $this->paymentMethod,
        ];

        foreach ($this->cart as $item) {
            $receiptData['items'][] = [
                'name' => $item['name'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'total' => $item['price'] * $item['quantity'],
                'customizations' => $item['customizations'] ?? [],
            ];
        }

        $this->dispatch('receipt-generated', ['receipt_data' => $receiptData]);
    }

    // =============== CART METHODS ===============
    public function addToCart(int $productId): void
    {
        $product = Product::find($productId);
        if (! $product) {
            return;
        }

        // Check if product can be produced with current inventory
        if (! $this->inventoryService->canProduceProduct($productId, 1)) {
            $this->dispatch('insufficient-inventory', [
                'message' => "Cannot add {$product->name}: Insufficient ingredients",
                'product_name' => $product->name,
            ]);

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
            // Check if we can add one more
            if (! $this->inventoryService->canProduceProduct($productId, $this->cart[$productId]['quantity'] + 1)) {
                $this->dispatch('insufficient-inventory', [
                    'message' => "Cannot add more {$product->name}: Insufficient ingredients",
                    'product_name' => $product->name,
                ]);

                return;
            }
            $this->cart[$productId]['quantity']++;
        }

        $this->calculateTotals();
        $this->updateProductAvailability();
        
        $this->dispatch('productSelected', $productId);
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
            if (! $this->inventoryService->canProduceProduct($productId, $this->cart[$productId]['quantity'] + 1)) {
                $this->dispatch('insufficient-inventory', [
                    'message' => 'Cannot increase quantity: Insufficient ingredients',
                    'product_id' => $productId,
                ]);

                return;
            }
            $this->cart[$productId]['quantity']++;
            $this->calculateTotals();
            $this->updateProductAvailability();
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

        $this->showPaymentModal = true;
    }

    private function checkLowStock(): void
    {
        $this->lowStockAlerts = $this->inventoryService->checkLowStock()
            ->map(function ($inventory) {
                return [
                    'ingredient_name' => $inventory->ingredient->name,
                    'current_stock' => $inventory->current_stock,
                    'min_stock_level' => $inventory->min_stock_level,
                    'unit_type' => $inventory->ingredient->unit_type,
                ];
            })
            ->toArray();
    }

    private function updateProductAvailability(): void
    {
        $cartProductIds = [];
        foreach (array_keys($this->cart) as $cartKey) {
            // Extract the base product ID from cart keys like "1_large_hot"
            $cartKey = (string) $cartKey; // Convert to string for explode
            $parts = explode('_', $cartKey);
            $cartProductIds[] = (int) $parts[0];
        }

        $allProductIds = array_merge(
            $cartProductIds,
            $this->products->pluck('id')->toArray()
        );

        $this->productAvailability = [];
        foreach ($allProductIds as $productId) {
            // Calculate total quantity in cart for this product (across all variants)
            $inCart = 0;
            foreach ($this->cart as $cartKey => $item) {
                if (str_starts_with((string)$cartKey, $productId.'_')) {
                    $inCart += $item['quantity'] ?? 0;
                }
            }

            $canProduceOne = $this->inventoryService->canProduceProduct($productId, 1);
            $canProduceMore = $this->inventoryService->canProduceProduct($productId, $inCart + 1);

            $this->productAvailability[$productId] = [
                'can_add' => $canProduceOne,
                'can_increment' => $canProduceMore,
                'max_quantity' => $this->getMaxProducibleQuantity($productId),
            ];
        }
    }

    private function getMaxProducibleQuantity(int $productId): int
    {
        $product = Product::find($productId);
        if (! $product) {
            return 0;
        }

        $ingredients = $product->ingredients()->with('ingredient.inventory')->get();
        $maxQuantities = [];

        foreach ($ingredients as $productIngredient) {
            $ingredient = $productIngredient->ingredient;
            if ($ingredient->is_trackable) {
                $inventory = $ingredient->inventory;
                if ($inventory) {
                    $maxQuantities[] = (int) ($inventory->current_stock / $productIngredient->quantity_required);
                } else {
                    return 0; // No inventory means can't produce
                }
            }
        }

        return empty($maxQuantities) ? 999 : min($maxQuantities);
    }

    private function calculateCustomizationPrice(array $customizations): float
    {
        $price = 0.0;

        // Add price for milk alternatives
        if (isset($customizations['milk']) && in_array($customizations['milk'], ['oat', 'almond', 'soy'])) {
            $price += 0.50;
        }

        // Add price for extra shots
        if (isset($customizations['extra_shots'])) {
            $price += $customizations['extra_shots'] * 0.75;
        }

        // Add price for syrups
        if (isset($customizations['syrup']) && $customizations['syrup'] !== 'none') {
            $price += 0.60;
        }

        return $price;
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
