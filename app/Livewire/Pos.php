<?php

declare(strict_types=1);

namespace App\Livewire;

use AllowDynamicProperties;
use App\Actions\PosCheckoutAction;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\PosService;
use Illuminate\View\View;
use Livewire\Component;

#[AllowDynamicProperties]
final class Pos extends Component
{
    // State properties
    public bool $isProcessing = false;

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

    public bool $showPaymentConfirmationModal = false;

    public array $paymentConfirmationData = [];

    public array $receiptData = [];

    // Coffee shop specific features
    public array $quickAddItems = [];

    public array $sizeOptions = ['small', 'medium', 'large'];

    public array $temperatureOptions = ['hot', 'iced', 'blended'];

    public array $milkOptions = ['whole', 'skim', 'oat', 'almond', 'soy'];

    public array $customizations = [];

    public $selectedProductId;

    public $selectedProductIds = [];

    // Success state
    public bool $showSuccessAnimation = false;

    public ?array $completedOrder = null;

    public float $amountTendered = 0;

    public array $lowStockAlerts = [];

    public array $productAvailability = [];

    protected $listeners = [
        'productSelected' => 'addToCart',
        'categorySelected' => 'selectCategory',
        'searchChanged' => 'updateSearch',
    ];

    // Service properties
    private PosCheckoutAction $posCheckoutAction;

    private PosService $posService;

    public function boot(
        PosCheckoutAction $posCheckoutAction,
        PosService $posService
    ): void {
        $this->posCheckoutAction = $posCheckoutAction;
        $this->posService = $posService;
    }

    public function toggleSelectProduct($productId): void
    {
        if (in_array($productId, $this->selectedProductIds)) {
            // Remove if already selected
            $this->selectedProductIds = array_diff($this->selectedProductIds, [$productId]);
        } else {
            // Add to selection
            $this->selectedProductIds[] = $productId;
        }
    }

    public function selectCategory(int $categoryId): void
    {
        $this->selectedCategory = $categoryId;
    }

    public function updateSearch(string $search): void
    {
        $this->search = $search;
    }

    public function selectPayment(string $method): void
    {
        $this->paymentMethod = $method;
    }

    public function confirmPayment(): void
    {
        if ($this->cart === []) {
            $this->dispatch('show-alert', 'Cart is empty!');

            return;
        }

        // Set processing state
        $this->isProcessing = true;
        $this->dispatch('processing-started');

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

        $this->isProcessing = false;

        if ($result['success']) {
            // Store cart count and items before clearing
            $cartCount = count($this->cart);
            $cartItems = $this->cart;

            // Store completed order data
            $this->completedOrder = [
                'order_number' => $result['order_number'],
                'order_id' => $result['order_id'],
                'customer_name' => $this->customerName ?: 'Guest',
                'order_type' => $this->orderType,
                'table_number' => $this->tableNumber,
                'cart_items' => $cartItems,
                'add_ons' => $this->addOns,
                'subtotal' => $this->subtotal,
                'discount_amount' => $this->discountAmount,
                'discount_percentage' => $this->discountPercentage,
                'discount_applied' => $this->discountApplied,
                'total' => $result['total'],
                'instructions' => $this->otherNote,
                'payment_method' => $this->paymentMethod,
                'items_count' => $cartCount,
                'amount_tendered' => $this->amountTendered,
                'change' => $this->paymentMethod === 'cash' && $this->amountTendered > 0
                    ? $this->amountTendered - $this->total
                    : 0,
            ];

            // Store receipt data BEFORE clearing cart
            $this->receiptData = $this->completedOrder;

            // Show success animation
            $this->showSuccessAnimation = true;

            // Prepare confirmation data for the modal
            $this->paymentConfirmationData = [
                'order_number' => $result['order_number'],
                'total' => $result['total'],
                'payment_method' => $this->paymentMethod,
                'customer_name' => $this->customerName ?: 'Guest',
                'order_type' => $this->orderType,
                'table_number' => $this->tableNumber,
                'items_count' => $cartCount,
            ];

            // Show the confirmation modal
            $this->showPaymentConfirmationModal = true;

            // Dispatch success event
            $this->dispatch('order-success', [
                'message' => $result['message'],
                'order_id' => $result['order_id'],
                'order_number' => $result['order_number'],
            ]);

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Order #'.$result['order_number'].' completed successfully!',
            ]);

            // Clear cart and reset workflow after a delay
            $this->clearCart();
            $this->showPaymentPanel = false;

            // Refresh data
            $this->loadRecentOrders();
            $this->checkLowStock();
            $this->updateProductAvailability();
        } else {
            $this->dispatch('order-failed', [
                'message' => $result['message'],
                'order_id' => null,
            ]);
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Order failed: '.$result['message'],
            ]);
            $this->dispatch('show-alert', 'Order failed: '.$result['message']);
        }
    }

    public function mount(): void
    {
        // Initialize products before using them
        $this->products = $this->posService->getFilteredProducts();

        $this->calculateTotals();
        $this->loadRecentOrders();
        $this->checkLowStock();
        $this->updateProductAvailability();
        $this->loadQuickAddItems();
    }

    public function render(): View
    {
        // Load products with availability info
        $this->products = $this->posService->getFilteredProducts($this->selectedCategory, $this->search);

        // Load best sellers based on actual metrics data
        $this->bestSellers = $this->posService->getBestSellers();

        // Load categories for sidebar navigation
        $this->categories = $this->posService->getActiveCategories();

        // Load recent orders for dashboard
        $this->recentOrders = Order::with('items.product')
            ->latest()
            ->take(5)
            ->get();

        // Calculate today's orders and sales
        $this->todayOrders = Order::query()->whereDate('created_at', today())->count();
        $this->todaySales = Order::query()->whereDate('created_at', today())->sum('total');

        // For POS functionality (when used as POS component)
        if ($this->customerSearch !== '' && $this->customerSearch !== '0') {
            $this->customers = Customer::query()->where('name', 'like', '%'.$this->customerSearch.'%')
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
        $this->quickAddItems = $this->posService->getQuickAddItems();
    }

    public function quickAddProduct(int $productId, string $size = 'medium', string $temperature = 'hot'): void
    {
        if (! $this->posService->canAddToCart($productId)) {
            $this->dispatch('insufficient-inventory', [
                'message' => 'Cannot add product: Insufficient ingredients',
                'product_id' => $productId,
            ]);

            return;
        }

        $product = Product::query()->find($productId);
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
            // @phpstan-ignore booleanNot.alwaysFalse
            if (! $this->posService->canAddToCart($productId)) {
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
            $product = Product::query()->find($this->cart[$cartItemId]['id']);
            if ($product) {
                $basePrice = $product->price;
                $customizationPrice = $this->posService->calculateCustomizationPrice($customizations);
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

        // First, validate that all items can be produced with current inventory
        foreach ($order->items as $item) {
            /** @var OrderItem $item */
            if (! $this->canProduceCartQuantity($item->product_id, $item->quantity)) {
                $productName = $item->product->name ?? 'Unknown Product';
                $this->dispatch('insufficient-inventory', [
                    'message' => "Cannot duplicate order: Insufficient ingredients for {$productName}",
                    'product_name' => $productName,
                ]);

                return;
            }
        }

        // If all validations pass, populate the cart
        $this->cart = [];
        foreach ($order->items as $item) {
            /** @var OrderItem $item */
            $cartItemId = $item->product_id;

            $this->cart[$cartItemId] = [
                'id' => $item->product_id,
                'name' => $item->product->name ?? 'Unknown Product',
                'price' => $item->price,
                'quantity' => $item->quantity,
                'image' => $item->product->image_url ?? '',
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
        $product = Product::query()->find($productId);
        if (! $product) {
            return;
        }

        // Check if product can be produced with current inventory
        if (! $this->posService->canAddToCart($productId)) {
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
            // @phpstan-ignore booleanNot.alwaysFalse
            if (! $this->posService->canAddToCart($productId)) {
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
            // Try to increment and check if we can still produce all items in cart
            $this->cart[$productId]['quantity']++;
            
            // Check if we can produce this quantity with current inventory
            $currentQuantity = $this->cart[$productId]['quantity'];
            if (! $this->canProduceCartQuantity($productId, $currentQuantity)) {
                // Revert the increment
                $this->cart[$productId]['quantity']--;
                
                $this->dispatch('insufficient-inventory', [
                    'message' => 'Cannot increase quantity: Insufficient ingredients',
                    'product_id' => $productId,
                ]);

                return;
            }
            
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

    public function getChangeAmount(): float
    {
        if ($this->paymentMethod === 'cash' && $this->amountTendered > 0) {
            return max(0, $this->amountTendered - $this->total);
        }

        return 0;
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
        $customer = Customer::query()->find($customerId);
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
            /** @var OrderItem $item */
            $this->cart[$item->product_id] = [
                'id' => $item->product_id,
                'name' => $item->product->name ?? 'Unknown Product',
                'price' => $item->price,
                'quantity' => $item->quantity,
                'image' => $item->product->image_url ?? '',
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
        if ($this->cart === []) {
            $this->dispatch('cart-empty', ['message' => 'Cart is empty']);

            return;
        }

        $this->showPaymentModal = true;
    }

    public function closePaymentConfirmationModal(): void
    {
        $this->showPaymentConfirmationModal = false;
        $this->paymentConfirmationData = [];
    }

    private function checkLowStock(): void
    {
        $this->lowStockAlerts = $this->posService->getLowStockAlerts();
    }

    private function updateProductAvailability(): void
    {
        $this->productAvailability = $this->posService->getPosProductAvailability($this->products, $this->cart);
    }

    private function loadRecentOrders(): void
    {
        $this->recentOrders = Order::query()->latest()->take(5)->get();
    }

    private function calculateTotals(): void
    {
        $totals = $this->posService->calculateCartTotals($this->cart, $this->addOns, $this->discountAmount);

        $this->subtotal = $totals['subtotal'];
        $this->taxAmount = $totals['tax_amount'];
        $this->total = $totals['total'];
    }

    private function canProduceCartQuantity(int $productId, int $quantity): bool
    {
        $product = Product::query()->find($productId);
        if (! $product) {
            return false;
        }

        $ingredients = $product->ingredients()->with('ingredient.inventory')->get();
        
        foreach ($ingredients as $productIngredient) {
            /** @var ProductIngredient $productIngredient */
            $ingredient = $productIngredient->ingredient;
            if ($ingredient === null) {
                return false;
            }

            /** @var Ingredient $ingredient */
            $inventory = $ingredient->inventory;
            if (! $inventory) {
                return false;
            }

            $requiredAmount = $productIngredient->quantity_required * $quantity;
            if ($inventory->current_stock < $requiredAmount) {
                return false;
            }
        }

        return true;
    }
}
