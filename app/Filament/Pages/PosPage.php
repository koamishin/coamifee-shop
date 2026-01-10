<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\Currency;
use App\Enums\DiscountType;
use App\Enums\TableNumber;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\GeneralSettingsService;
use App\Services\PosService;
use BackedEnum;
// use App\Models\Category; // Not used directly, using PosService instead
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Locked;

final class PosPage extends Page
{
    public array $cartItems = [];

    public ?int $selectedCategoryId = null;

    public string $search = '';

    public ?int $customerId = null;

    public string $customerName = '';

    public string $orderType = 'dine-in';

    public ?string $tableNumber = null;

    public string $notes = '';

    public string $paymentTiming = 'pay_later';

    public string $paymentMethod = 'cash';

    public float $totalAmount = 0.0;

    public float $paidAmount = 0.0;

    public float $changeAmount = 0.0;

    public bool $isTabletMode = false;

    public ?string $discountType = null;

    public ?float $discountValue = null;

    public array $addOns = [];

    public ?int $selectedProductForVariant = null;

    public ?int $selectedVariantId = null;

    public ?string $creationDate = null;

    #[Locked]
    public ?int $currentOrderId = null;

    public \Illuminate\Support\Collection $categories;

    public \Illuminate\Support\Collection $products;

    public Collection $customers;

    public Currency $currency;

    public array $productAvailability = [];

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-shopping-cart';

    // protected static UnitEnum|string|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'POS System';

    protected static ?string $title = 'Point of Sale';

    protected string $view = 'filament.pages.pos-page';

    private PosService $posService;

    private GeneralSettingsService $settingsService;

    public function mount(): void
    {
        $this->loadData();

        // Load tablet mode preference from session (tablet-first default)
        $this->isTabletMode = session('pos_tablet_mode', true);

        if (! session()->has('pos_tablet_mode')) {
            session(['pos_tablet_mode' => $this->isTabletMode]);
        }
    }

    public function boot(PosService $posService, GeneralSettingsService $settingsService): void
    {
        $this->posService = $posService;
        $this->settingsService = $settingsService;

        // Initialize currency from settings
        $currencyCode = $this->settingsService->getCurrency();
        $this->currency = Currency::from($currencyCode);
    }

    public function loadData(): void
    {
        $this->categories = $this->posService->getActiveCategories();
        $this->customers = Customer::query()
            ->withCount('orders')
            ->orderByDesc('orders_count')
            ->orderBy('name')
            ->get();
        $this->refreshProducts();
    }

    public function refreshProducts(): void
    {
        $this->products = $this->posService->getFilteredProducts(
            $this->selectedCategoryId,
            $this->search
        );

        // Update product availability
        $this->productAvailability = $this->posService->updateProductAvailability($this->products);
    }

    public function selectCategory(?int $categoryId): void
    {
        $this->selectedCategoryId = $categoryId;
        $this->refreshProducts();
    }

    public function setOrderType(string $orderType): void
    {
        $this->orderType = $orderType;

        if ($this->orderType !== 'dine-in') {
            $this->tableNumber = null;
        }

        if ($this->orderType !== 'dine-in') {
            $this->paymentTiming = 'pay_now';
        }

        if ($this->orderType === 'delivery' && ! in_array($this->paymentMethod, ['grab', 'food_panda'], true)) {
            $this->paymentMethod = 'grab';
        }
    }

    public function selectTable(string $tableNumber): void
    {
        $this->tableNumber = $tableNumber;
    }

    public function addToCart(int $productId, ?int $variantId = null): void
    {
        // Check if product can be added to cart
        if (! $this->posService->canAddToCart($productId)) {
            Notification::make()
                ->danger()
                ->title('Cannot add to cart')
                ->body('This product is currently out of stock or unavailable')
                ->send();

            return;
        }

        /** @var \App\Models\Product|null $product */
        $product = $this->products->firstWhere('id', $productId);

        if (! $product) {
            Notification::make()
                ->danger()
                ->title('Product not found')
                ->send();

            return;
        }

        // Check if product has variants and no variant was selected
        if ($product->hasVariants() && ! $variantId) {
            // Store product for variant selection and trigger modal
            $this->selectedProductForVariant = $productId;

            return;
        }

        // Get variant information if variant is selected
        $variant = null;
        $variantName = null;
        $productPrice = $product->price;

        if ($variantId) {
            /** @var \App\Models\ProductVariant|null $variant */
            $variant = $product->activeVariants()->find($variantId);
            if ($variant) {
                $variantName = $variant->name;
                $productPrice = $variant->price;
            }
        }
        $existingItemKey = array_find_key($this->cartItems, fn ($item): bool => $item['product_id'] === $productId && ($item['variant_id'] ?? null) === $variantId);

        if ($existingItemKey !== null) {
            $existingItem = $this->cartItems[$existingItemKey];
            // Check if we can increment quantity
            $newQuantity = (int) $existingItem['quantity'] + 1;
            $maxQuantity = $this->posService->getMaxProducibleQuantity($productId);

            if ($newQuantity > $maxQuantity) {
                Notification::make()
                    ->warning()
                    ->title('Maximum quantity reached')
                    ->body("Only {$maxQuantity} items can be ordered based on available inventory")
                    ->send();

                return;
            }

            $this->cartItems[$existingItemKey]['quantity'] = $newQuantity;
            $this->cartItems[$existingItemKey]['subtotal'] = $newQuantity * $this->cartItems[$existingItemKey]['price'];
        } else {
            $displayName = $product->name;
            if ($variantName) {
                $displayName .= " ({$variantName})";
            }

            $this->cartItems[] = [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'variant_name' => $variantName,
                'name' => $displayName,
                'price' => $productPrice,
                'quantity' => 1,
                'subtotal' => $productPrice,
                'discount_type' => null,
                'discount_percentage' => 0,
                'discount_amount' => 0,
            ];
        }

        $this->calculateTotals();

        $notificationProductName = $product->name;
        if ($variantName !== null) {
            $notificationProductName .= " ({$variantName})";
        }

        Notification::make()
            ->success()
            ->title('Added to cart')
            ->body("{$notificationProductName} added to cart")
            ->send();

        // Clear variant selection
        $this->selectedProductForVariant = null;
        $this->selectedVariantId = null;
    }

    public function removeFromCart(int $index): void
    {
        unset($this->cartItems[$index]);
        $this->cartItems = array_values($this->cartItems);
        $this->calculateTotals();
    }

    public function updateQuantity(int $index, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->removeFromCart($index);

            return;
        }

        if (isset($this->cartItems[$index])) {
            $productId = $this->cartItems[$index]['product_id'];
            $maxQuantity = $this->posService->getMaxProducibleQuantity($productId);

            if ($quantity > $maxQuantity) {
                Notification::make()
                    ->warning()
                    ->title('Maximum quantity reached')
                    ->body("Only {$maxQuantity} items can be ordered based on available inventory")
                    ->send();

                return;
            }

            $this->cartItems[$index]['quantity'] = $quantity;
            $this->cartItems[$index]['subtotal'] = $quantity * $this->cartItems[$index]['price'];

            // Recalculate item discount amount based on new subtotal
            $discountPercentage = $this->cartItems[$index]['discount_percentage'] ?? 0;
            $originalSubtotal = $this->cartItems[$index]['subtotal'];
            $discountAmount = $discountPercentage > 0 ? ($originalSubtotal * $discountPercentage / 100) : 0;
            $this->cartItems[$index]['discount_amount'] = $discountAmount;

            $this->calculateTotals();
        }
    }

    public function updatedPaidAmount(?float $value): void
    {
        if ($value !== null) {
            $this->paidAmount = $value;
        }
        $this->calculateTotals();
    }

    public function updatedCartItems(mixed $value, mixed $key): void
    {
        // Handle live updates to cart items
        if (! is_string($key)) {
            return;
        }

        if (mb_strpos($key, '.discount_type') !== false) {
            $this->updatedCartItemDiscountType($key, (string) $value);

            return;
        }

        if (mb_strpos($key, '.discount_percentage') !== false) {
            $this->updatedCartItemDiscountPercentage($key, (float) $value);
        }
    }

    public function updatedCartItemDiscountType(string $key, string $value): void
    {
        $index = (int) explode('.', $key)[0];

        if (isset($this->cartItems[$index])) {
            $this->cartItems[$index]['discount_type'] = $value ?: null;

            // Auto-fill percentage if it's a predefined discount type
            if ($value !== '' && $value !== '0') {
                $discountType = DiscountType::tryFrom($value);
                if ($discountType) {
                    $percentage = $discountType->getPercentage();
                    $this->cartItems[$index]['discount_percentage'] = $percentage;
                }
            } else {
                $this->cartItems[$index]['discount_percentage'] = 0;
            }

            // Recalculate discount amount
            $this->recalculateItemDiscount($index);
            $this->calculateTotals();
        }
    }

    public function updatedCartItemDiscountPercentage(string $key, float $value): void
    {
        $index = (int) explode('.', $key)[0];

        if (isset($this->cartItems[$index])) {
            $this->cartItems[$index]['discount_percentage'] = $value;

            // Clear discount type if custom percentage is entered
            if ($value > 0 && ! $this->cartItems[$index]['discount_type']) {
                $this->cartItems[$index]['discount_type'] = null;
            }

            // Recalculate discount amount
            $this->recalculateItemDiscount($index);
            $this->calculateTotals();
        }
    }

    public function updatedSearch(): void
    {
        $this->refreshProducts();
    }

    public function clearCart(): void
    {
        $this->cartItems = [];
        $this->calculateTotals();

        Notification::make()
            ->info()
            ->title('Cart cleared')
            ->send();
    }

    public function toggleMode(): void
    {
        $this->isTabletMode = ! $this->isTabletMode;

        // Save preference to session
        session(['pos_tablet_mode' => $this->isTabletMode]);

        Notification::make()
            ->success()
            ->title('Mode Changed')
            ->body($this->isTabletMode ? 'Switched to Tablet Mode' : 'Switched to Desktop Mode')
            ->send();
    }

    public function selectVariant(int $variantId): void
    {
        if ($this->selectedProductForVariant) {
            $this->addToCart($this->selectedProductForVariant, $variantId);
        }
    }

    public function closeVariantSelection(): void
    {
        $this->selectedProductForVariant = null;
        $this->selectedVariantId = null;
    }

    public function createOrder(): void
    {
        if ($this->cartItems === []) {
            Notification::make()
                ->warning()
                ->title('Cart is empty')
                ->body('Please add items to the cart before creating the order')
                ->send();

            return;
        }

        if ($this->orderType === 'dine-in' && blank($this->tableNumber)) {
            Notification::make()
                ->warning()
                ->title('Table is required')
                ->body('Please select a table number for dine-in orders')
                ->send();

            return;
        }

        try {
            DB::beginTransaction();

            // Calculate ORIGINAL subtotal (before any item-level discounts)
            $originalSubtotal = 0.0;
            foreach ($this->cartItems as $item) {
                $originalSubtotal += (float) $item['subtotal'];
            }

            // Calculate total item-level discounts
            $itemLevelDiscountTotal = 0.0;
            foreach ($this->cartItems as $item) {
                $itemSubtotal = (float) $item['subtotal'];
                $itemDiscountPercentage = $item['discount_percentage'] ?? 0;
                if ($itemDiscountPercentage > 0) {
                    $itemDiscountAmount = $itemSubtotal * ($itemDiscountPercentage / 100);
                    $itemLevelDiscountTotal += $itemDiscountAmount;
                }
            }

            // Calculate order-level discount (applied to original subtotal, NOT after item discounts)
            $orderLevelDiscountAmount = 0.0;
            if (! in_array($this->discountType, [null, '', '0'], true) && ! empty($this->discountValue)) {
                // Order-level discount applies to original subtotal
                $orderLevelDiscountAmount = $originalSubtotal * ($this->discountValue / 100);
            }

            // Calculate add-ons total
            $addOnsTotal = 0.0;
            foreach ($this->addOns as $addOn) {
                if (! empty($addOn['price'])) {
                    $addOnsTotal += (float) $addOn['price'];
                }
            }

            // Final total = original subtotal - item discounts - order discount + add-ons
            $finalTotal = $originalSubtotal - $itemLevelDiscountTotal - $orderLevelDiscountAmount + $addOnsTotal;

            // Determine payment status and method based on payment timing
            $paymentStatus = $this->paymentTiming === 'pay_now' ? 'paid' : 'unpaid';
            $paymentMethod = $this->paymentTiming === 'pay_now' ? $this->paymentMethod : null;

            $creationDate = $this->creationDate ? Date::parse($this->creationDate) : now();

            // Prepare order data
            $orderData = [
                'customer_id' => $this->customerId,
                'customer_name' => $this->customerName ?: 'Walk-in Customer',
                'order_type' => $this->orderType,
                'table_number' => $this->tableNumber,
                'notes' => $this->notes,
                'subtotal' => $originalSubtotal,  // Original subtotal before any discounts
                'discount_type' => $this->discountType,
                'discount_value' => $this->discountValue,
                'discount_amount' => $orderLevelDiscountAmount,  // Only order-level discount (item discounts are stored per-item)
                'add_ons' => $this->addOns === [] ? null : $this->addOns,
                'add_ons_total' => $addOnsTotal,
                'total' => $finalTotal,  // Final total after all discounts and add-ons
                'status' => 'pending',
                'payment_status' => $paymentStatus,
                'payment_method' => $paymentMethod,
                'created_at' => $creationDate,
                'updated_at' => $creationDate,
            ];

            // Add paid amount and change if paying now
            if ($this->paymentTiming === 'pay_now') {
                // For delivery orders or non-cash payments, set exact payment
                if ($this->orderType === 'delivery' && in_array($this->paymentMethod, ['grab', 'food_panda'])) {
                    $orderData['paid_amount'] = $finalTotal;
                    $orderData['change_amount'] = 0;
                } elseif ($this->paymentMethod === 'cash' && $this->paidAmount > 0) {
                    // Cash payment with custom amount
                    $orderData['paid_amount'] = $this->paidAmount;
                    $orderData['change_amount'] = $this->changeAmount;
                } else {
                    // Non-cash payments (GCash, Maya, Bank Transfer) - exact amount
                    $orderData['paid_amount'] = $finalTotal;
                    $orderData['change_amount'] = 0;
                }
            }

            $order = Order::query()->create($orderData);

            Log::info('POS Order Created', [
                'order_id' => $order->id,
                'order_type' => $orderData['order_type'],
                'payment_timing' => $this->paymentTiming,
                'cart_items_count' => count($this->cartItems),
                'original_subtotal' => $originalSubtotal,
                'item_level_discount_total' => $itemLevelDiscountTotal,
                'order_level_discount' => $orderLevelDiscountAmount,
                'add_ons_total' => $addOnsTotal,
                'final_total' => $finalTotal,
            ]);

            foreach ($this->cartItems as $item) {
                $itemSubtotal = (float) $item['subtotal'];
                $discountPercentage = $item['discount_percentage'] ?? 0;
                $discountAmount = $discountPercentage > 0 ? ($itemSubtotal * $discountPercentage / 100) : 0;
                $itemFinalSubtotal = $itemSubtotal - $discountAmount;

                $orderItemData = [
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['variant_id'] ?? null,
                    'variant_name' => $item['variant_name'] ?? null,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['subtotal'],
                    'discount_percentage' => $discountPercentage,
                    'discount_amount' => $discountAmount,
                    'discount' => $discountAmount, // Using the same value for legacy compatibility
                    'created_at' => $creationDate,
                    'updated_at' => $creationDate,
                ];

                Log::info('Creating Order Item with Discount', [
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'] ?? 'Unknown',
                    'item_subtotal' => $itemSubtotal,
                    'discount_percentage' => $discountPercentage,
                    'discount_amount' => $discountAmount,
                    'item_final_subtotal' => $itemFinalSubtotal,
                    'data_to_save' => $orderItemData,
                ]);

                $createdItem = OrderItem::query()->create($orderItemData);

                Log::info('Order Item Created - Verification', [
                    'item_id' => $createdItem->id,
                    'saved_discount_percentage' => $createdItem->discount_percentage,
                    'saved_discount_amount' => $createdItem->discount_amount,
                    'saved_discount' => $createdItem->discount,
                    'saved_subtotal' => $createdItem->subtotal,
                ]);
            }

            DB::commit();

            $this->currentOrderId = $order->id;

            $paymentStatusText = $paymentStatus === 'paid' ? ' (Payment received)' : ' (Payment pending)';

            Notification::make()
                ->success()
                ->title('Order created successfully!')
                ->body("Order #{$order->id} for {$this->tableNumber} has been sent to kitchen{$paymentStatusText}")
                ->duration(5000)
                ->send();

            $this->resetOrder();
            $this->refreshProducts();
        } catch (Exception $e) {
            DB::rollBack();

            Notification::make()
                ->danger()
                ->title('Error creating order')
                ->body($e->getMessage())
                ->send();
        }
    }

    public function resetOrder(): void
    {
        $this->cartItems = [];
        $this->customerId = null;
        $this->customerName = '';
        $this->orderType = 'dine-in';
        $this->tableNumber = null;
        $this->notes = '';
        $this->paymentTiming = 'pay_later';
        $this->paymentMethod = 'cash';
        $this->paidAmount = 0.0;
        $this->discountType = null;
        $this->discountValue = null;
        $this->addOns = [];
        $this->creationDate = now()->toDateTimeString();
        $this->calculateTotals();
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    /**
     * Format amount with currency symbol
     */
    public function formatCurrency(float|int|string|null $amount): string
    {
        // Handle null values gracefully
        if ($amount === null) {
            return $this->currency->formatAmount(0.0);
        }

        return $this->currency->formatAmount((float) $amount);
    }

    /**
     * Get currency symbol
     */
    public function getCurrencySymbol(): string
    {
        return $this->currency->getSymbol();
    }

    /**
     * Get currency decimals
     */
    public function getCurrencyDecimals(): int
    {
        return $this->currency->getDecimals();
    }

    /**
     * Check if product can be added to cart
     */
    public function canAddToCart(int $productId): bool
    {
        return $this->posService->canAddToCart($productId);
    }

    /**
     * Get stock status for product
     */
    public function getStockStatus(int $productId): string
    {
        return $this->posService->getStockStatus($productId);
    }

    /**
     * Get maximum producible quantity for product
     */
    public function getMaxProducibleQuantity(int $productId): int
    {
        return $this->posService->getMaxProducibleQuantity($productId);
    }

    /**
     * Get product image URL from R2
     */
    public function getProductImageUrl(?string $imagePath): ?string
    {
        if (! $imagePath) {
            return null;
        }

        if (str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) {
            return $imagePath;
        }

        $baseUrl = (string) config('filesystems.disks.r2.url', '');
        if ($baseUrl === '') {
            return null;
        }

        return mb_rtrim($baseUrl, '/').'/'.mb_ltrim($imagePath, '/');
    }

    protected function getActions(): array
    {
        return [
            Action::make('newOrder')
                ->label('New Order')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->action(fn () => $this->resetOrder()),

            Action::make('clearCart')
                ->label('Clear Cart')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->action(fn () => $this->clearCart())
                ->hidden(fn (): bool => $this->cartItems === []),

            Action::make('placeOrder')
                ->label('Send Order')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->fillForm(fn (): array => [
                    'orderType' => $this->orderType,
                    'tableNumber' => $this->tableNumber,
                    'customerId' => $this->customerId,
                    'customerName' => $this->customerName,
                    'customerQuick' => $this->customerId !== null
                        && $this->customers->take(8)->pluck('id')->contains($this->customerId)
                            ? (string) $this->customerId
                            : 'walk_in',
                    'notes' => $this->notes,
                    'paymentTiming' => $this->paymentTiming,
                    'deliveryProvider' => in_array($this->paymentMethod, ['grab', 'food_panda'], true)
                        ? $this->paymentMethod
                        : 'grab',
                    'paymentMethod' => $this->paymentMethod,
                    'paidAmount' => $this->paidAmount,
                    'changeAmount' => $this->changeAmount,
                    'discountType' => $this->discountType,
                    'discountValue' => $this->discountValue,
                    'addOns' => $this->addOns,
                    'creationDate' => $this->creationDate ?? now()->toDateTimeString(),
                ])
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Section::make('Order')
                                ->schema([
                                    DateTimePicker::make('creationDate')
                                        ->label('Order Date')
                                        ->required()
                                        ->seconds(false)
                                        ->default(now())
                                        ->columnSpanFull(),

                                    ToggleButtons::make('orderType')
                                        ->label('Order type')
                                        ->options([
                                            'dine-in' => 'Dine In',
                                            'takeout' => 'Takeout',
                                            'delivery' => 'Delivery',
                                        ])
                                        ->grouped()
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function (?string $state, callable $set, $get): void {
                                            if ($state === null) {
                                                return;
                                            }

                                            $this->setOrderType($state);

                                            if ($state !== 'dine-in') {
                                                $set('tableNumber', null);
                                                $set('paymentTiming', 'pay_now');
                                            }

                                            if ($state === 'delivery') {
                                                $selectedProvider = $get('deliveryProvider');
                                                $selectedPaymentMethod = $get('paymentMethod');

                                                $provider = in_array($selectedProvider, ['grab', 'food_panda'], true)
                                                    ? $selectedProvider
                                                    : (in_array($selectedPaymentMethod, ['grab', 'food_panda'], true)
                                                        ? $selectedPaymentMethod
                                                        : (in_array($this->paymentMethod, ['grab', 'food_panda'], true) ? $this->paymentMethod : 'grab'));

                                                $this->paymentTiming = 'pay_now';
                                                $this->paymentMethod = $provider;

                                                $set('deliveryProvider', $provider);
                                                $set('paymentMethod', $provider);

                                                return;
                                            }

                                            if (in_array($this->paymentMethod, ['grab', 'food_panda'], true)) {
                                                $this->paymentMethod = 'cash';
                                                $set('paymentMethod', 'cash');
                                            }
                                        })
                                        ->columnSpanFull(),

                                    ToggleButtons::make('tableNumber')
                                        ->label('Table (dine-in)')
                                        ->options(TableNumber::getOptions())
                                        ->columns(5)
                                        ->required(fn ($get): bool => ($get('orderType') ?? $this->orderType) === 'dine-in')
                                        ->visible(fn ($get): bool => ($get('orderType') ?? $this->orderType) === 'dine-in')
                                        ->live()
                                        ->afterStateUpdated(function (?string $state): void {
                                            if ($state === null) {
                                                return;
                                            }

                                            $this->selectTable($state);
                                        })
                                        ->columnSpanFull(),

                                    ToggleButtons::make('deliveryProvider')
                                        ->label('Delivery provider')
                                        ->options([
                                            'grab' => 'Grab',
                                            'food_panda' => 'Food Panda',
                                        ])
                                        ->grouped()
                                        ->columns(2)
                                        ->required(fn ($get): bool => ($get('orderType') ?? $this->orderType) === 'delivery')
                                        ->visible(fn ($get): bool => ($get('orderType') ?? $this->orderType) === 'delivery')
                                        ->default(fn (): string => in_array($this->paymentMethod, ['grab', 'food_panda'], true) ? $this->paymentMethod : 'grab')
                                        ->live()
                                        ->afterStateUpdated(function (?string $state, callable $set): void {
                                            if ($state === null) {
                                                return;
                                            }

                                            $this->paymentTiming = 'pay_now';
                                            $this->paymentMethod = $state;

                                            $set('paymentTiming', 'pay_now');
                                            $set('paymentMethod', $state);
                                        })
                                        ->columnSpanFull(),

                                    Hidden::make('customerId'),
                                    Hidden::make('customerName'),

                                    ToggleButtons::make('customerQuick')
                                        ->label('Customer')
                                        ->options(function (): array {
                                            $options = ['walk_in' => 'Walk-in'];

                                            foreach ($this->customers->take(8) as $customer) {
                                                /** @var Customer $customer */
                                                $options[(string) $customer->id] = $customer->name;
                                            }

                                            return $options;
                                        })
                                        ->columns(3)
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function (?string $state, callable $set): void {
                                            if (blank($state) || $state === 'walk_in') {
                                                $this->customerId = null;
                                                $this->customerName = '';

                                                $set('customerId', null);
                                                $set('customerName', '');

                                                return;
                                            }

                                            $customerId = (int) $state;
                                            /** @var Customer|null $customer */
                                            $customer = $this->customers->firstWhere('id', $customerId);

                                            $this->customerId = $customerId;
                                            $this->customerName = $customer ? $customer->name : '';

                                            $set('customerId', $customerId);
                                            $set('customerName', $this->customerName);
                                        })
                                        ->columnSpanFull(),

                                    Hidden::make('notes'),

                                    ToggleButtons::make('notesPresets')
                                        ->label('Notes')
                                        ->options(function ($get): array {
                                            $orderType = $get('orderType') ?? $this->orderType;

                                            if ($orderType === 'delivery') {
                                                return [
                                                    'call_on_arrival' => 'Call on arrival',
                                                    'leave_at_door' => 'Leave at door',
                                                    'no_contact' => 'No contact',
                                                    'gate_guard' => 'Gate/guard',
                                                    'fragile' => 'Handle with care',
                                                    'deliver_asap' => 'Deliver ASAP',
                                                ];
                                            }

                                            return [
                                                'no_sugar' => 'No sugar',
                                                'less_sugar' => 'Less sugar',
                                                'extra_hot' => 'Extra hot',
                                                'less_ice' => 'Less ice',
                                                'no_ice' => 'No ice',
                                                'extra_ice' => 'Extra ice',
                                                'no_whip' => 'No whip',
                                                'extra_shot' => 'Extra shot',
                                            ];
                                        })
                                        ->multiple()
                                        ->columns(3)
                                        ->live()
                                        ->dehydrated(false)
                                        ->afterStateUpdated(function (?array $state, callable $set, $get): void {
                                            $presets = $state ?? [];

                                            $orderType = $get('orderType') ?? $this->orderType;

                                            $map = $orderType === 'delivery'
                                                ? [
                                                    'call_on_arrival' => 'Call on arrival',
                                                    'leave_at_door' => 'Leave at door',
                                                    'no_contact' => 'No contact',
                                                    'gate_guard' => 'Gate/guard',
                                                    'fragile' => 'Handle with care',
                                                    'deliver_asap' => 'Deliver ASAP',
                                                ]
                                                : [
                                                    'no_sugar' => 'No sugar',
                                                    'less_sugar' => 'Less sugar',
                                                    'extra_hot' => 'Extra hot',
                                                    'less_ice' => 'Less ice',
                                                    'no_ice' => 'No ice',
                                                    'extra_ice' => 'Extra ice',
                                                    'no_whip' => 'No whip',
                                                    'extra_shot' => 'Extra shot',
                                                ];

                                            $notes = collect($presets)
                                                ->map(fn (string $key): ?string => $map[$key] ?? null)
                                                ->filter()
                                                ->values()
                                                ->implode(', ');

                                            $this->notes = $notes;
                                            $set('notes', $notes);
                                        })
                                        ->columnSpanFull(),
                                ])
                                ->columns(1),

                            Section::make('Payment')
                                ->schema([
                                    ToggleButtons::make('paymentTiming')
                                        ->label('Payment timing')
                                        ->options([
                                            'pay_later' => 'Pay later',
                                            'pay_now' => 'Pay now',
                                        ])
                                        ->grouped()
                                        ->required(fn ($get): bool => ($get('orderType') ?? $this->orderType) === 'dine-in')
                                        ->default(fn ($get): string => ($get('orderType') ?? $this->orderType) === 'dine-in' ? 'pay_later' : 'pay_now')
                                        ->visible(fn ($get): bool => ($get('orderType') ?? $this->orderType) === 'dine-in')
                                        ->live()
                                        ->afterStateUpdated(function (?string $state): void {
                                            if ($state === null) {
                                                return;
                                            }

                                            $this->paymentTiming = $state;
                                        })
                                        ->columnSpanFull(),

                                    ToggleButtons::make('paymentMethod')
                                        ->label('Payment method')
                                        ->options([
                                            'cash' => 'Cash',
                                            'gcash' => 'GCash',
                                            'maya' => 'Maya',
                                            'bank_transfer' => 'Bank',
                                        ])
                                        ->grouped()
                                        ->default('cash')
                                        ->visible(fn ($get): bool => ($get('paymentTiming') ?? $this->paymentTiming) === 'pay_now'
                                            && ($get('orderType') ?? $this->orderType) !== 'delivery')
                                        ->live()
                                        ->afterStateUpdated(function (?string $state): void {
                                            if ($state === null) {
                                                return;
                                            }

                                            $this->paymentMethod = $state;
                                        })
                                        ->columnSpanFull(),

                                    ToggleButtons::make('cashTender')
                                        ->label('Quick cash')
                                        ->options([
                                            'exact' => 'Exact',
                                            'next_50' => 'Next 50',
                                            'next_100' => 'Next 100',
                                            'next_500' => 'Next 500',
                                        ])
                                        ->grouped()
                                        ->dehydrated(false)
                                        ->visible(fn ($get): bool => ($get('paymentTiming') ?? $this->paymentTiming) === 'pay_now'
                                            && ($get('paymentMethod') ?? $this->paymentMethod) === 'cash'
                                            && ! $this->isTabletMode)
                                        ->live()
                                        ->afterStateUpdated(function (?string $state, callable $set, $get): void {
                                            if ($state === null) {
                                                return;
                                            }

                                            $originalSubtotal = collect($this->cartItems)->sum(fn (array $item): float => (float) ($item['subtotal'] ?? 0));
                                            $itemDiscountTotal = collect($this->cartItems)->sum(function (array $item): float {
                                                $subtotal = (float) ($item['subtotal'] ?? 0);
                                                $percentage = (float) ($item['discount_percentage'] ?? 0);

                                                return $percentage > 0 ? $subtotal * ($percentage / 100) : 0.0;
                                            });

                                            $orderDiscountAmount = 0.0;
                                            if (filled($get('discountValue'))) {
                                                $orderDiscountAmount = $originalSubtotal * ((float) $get('discountValue') / 100);
                                            }

                                            $addOnsTotal = 0.0;
                                            foreach (($get('addOns') ?? []) as $addOn) {
                                                if (! empty($addOn['price'])) {
                                                    $addOnsTotal += (float) $addOn['price'];
                                                }
                                            }

                                            $finalTotal = $originalSubtotal - $itemDiscountTotal - $orderDiscountAmount + $addOnsTotal;

                                            $tendered = match ($state) {
                                                'exact' => $finalTotal,
                                                'next_50' => ceil($finalTotal / 50) * 50,
                                                'next_100' => ceil($finalTotal / 100) * 100,
                                                'next_500' => ceil($finalTotal / 500) * 500,
                                                default => $finalTotal,
                                            };

                                            $set('paidAmount', $tendered);
                                            $this->paidAmount = $tendered;
                                            $this->calculateTotals();
                                        })
                                        ->columnSpanFull(),

                                    TextInput::make('paidAmount')
                                        ->label('Cash received')
                                        ->numeric()
                                        ->step(0.01)
                                        ->prefix($this->getCurrencySymbol())
                                        ->default(0)
                                        ->dehydrated(fn (): bool => ! $this->isTabletMode)
                                        ->required(fn ($get): bool => ($get('paymentTiming') ?? $this->paymentTiming) === 'pay_now'
                                            && ($get('paymentMethod') ?? $this->paymentMethod) === 'cash'
                                            && ! $this->isTabletMode)
                                        ->visible(fn ($get): bool => ($get('paymentTiming') ?? $this->paymentTiming) === 'pay_now'
                                            && ($get('paymentMethod') ?? $this->paymentMethod) === 'cash'
                                            && ! $this->isTabletMode)
                                        ->live()
                                        ->afterStateUpdated(function ($state): void {
                                            $this->paidAmount = (float) ($state ?? 0);
                                            $this->calculateTotals();
                                        })
                                        ->columnSpanFull(),

                                    View::make('filament.pages.pos.modals.cash-numpad-sheet')
                                        ->viewData(function ($get): array {
                                            $originalSubtotal = collect($this->cartItems)->sum(fn (array $item): float => (float) ($item['subtotal'] ?? 0));
                                            $itemDiscountTotal = collect($this->cartItems)->sum(function (array $item): float {
                                                $subtotal = (float) ($item['subtotal'] ?? 0);
                                                $percentage = (float) ($item['discount_percentage'] ?? 0);

                                                return $percentage > 0 ? $subtotal * ($percentage / 100) : 0.0;
                                            });

                                            $discountAmount = 0.0;
                                            if (filled($get('discountValue'))) {
                                                $discountAmount = $originalSubtotal * ((float) $get('discountValue') / 100);
                                            }

                                            $addOnsTotal = 0.0;
                                            foreach (($get('addOns') ?? []) as $addOn) {
                                                if (! empty($addOn['price'])) {
                                                    $addOnsTotal += (float) $addOn['price'];
                                                }
                                            }

                                            $finalTotal = $originalSubtotal - $itemDiscountTotal - $discountAmount + $addOnsTotal;

                                            return [
                                                'currency' => $this->getCurrencySymbol(),
                                                'initial' => (string) $this->paidAmount,
                                                'total' => $finalTotal,
                                            ];
                                        })
                                        ->visible(fn ($get): bool => ($get('paymentTiming') ?? $this->paymentTiming) === 'pay_now'
                                            && ($get('paymentMethod') ?? $this->paymentMethod) === 'cash'
                                            && $this->isTabletMode)
                                        ->columnSpanFull(),

                                    View::make('filament.pages.pos.modals.place-order-totals')
                                        ->viewData(function ($get): array {
                                            $originalSubtotal = collect($this->cartItems)->sum(fn (array $item): float => (float) ($item['subtotal'] ?? 0));
                                            $itemDiscountTotal = collect($this->cartItems)->sum(function (array $item): float {
                                                $subtotal = (float) ($item['subtotal'] ?? 0);
                                                $percentage = (float) ($item['discount_percentage'] ?? 0);

                                                return $percentage > 0 ? $subtotal * ($percentage / 100) : 0.0;
                                            });

                                            $discountAmount = 0.0;
                                            if (filled($get('discountValue'))) {
                                                $discountAmount = $originalSubtotal * ((float) $get('discountValue') / 100);
                                            }

                                            $addOnsTotal = 0.0;
                                            foreach (($get('addOns') ?? []) as $addOn) {
                                                if (! empty($addOn['price'])) {
                                                    $addOnsTotal += (float) $addOn['price'];
                                                }
                                            }

                                            $finalTotal = $originalSubtotal - $itemDiscountTotal - $discountAmount + $addOnsTotal;

                                            $paidAmount = $this->isTabletMode
                                                ? $this->paidAmount
                                                : (float) ($get('paidAmount') ?? 0);

                                            $changeAmount = $paidAmount - $finalTotal;

                                            return [
                                                'subtotal' => $originalSubtotal - $itemDiscountTotal,
                                                'discountAmount' => $discountAmount,
                                                'addOnsTotal' => $addOnsTotal,
                                                'finalTotal' => $finalTotal,
                                                'paidAmount' => $paidAmount,
                                                'changeAmount' => $changeAmount,
                                                'paymentTiming' => (string) ($get('paymentTiming') ?? $this->paymentTiming),
                                                'paymentMethod' => (string) ($get('paymentMethod') ?? $this->paymentMethod),
                                                'formatCurrency' => $this->formatCurrency(...),
                                            ];
                                        })
                                        ->columnSpanFull(),

                                    Hidden::make('changeAmount')
                                        ->default(0),
                                ]),
                        ])
                        ->columnSpanFull(),

                    Section::make('Extras')
                        ->schema([
                            ToggleButtons::make('discountType')
                                ->label('Discount type')
                                ->options(['' => 'None'] + DiscountType::getOptions())
                                ->grouped()
                                ->live()
                                ->afterStateUpdated(function (?string $state, callable $set): void {
                                    if (blank($state)) {
                                        $set('discountValue', null);

                                        return;
                                    }

                                    $discountType = DiscountType::from($state);
                                    $set('discountValue', $discountType->getPercentage());
                                })
                                ->columnSpanFull(),

                            TextInput::make('discountValue')
                                ->label('Discount %')
                                ->numeric()
                                ->disabled()
                                ->dehydrated()
                                ->visible(fn ($get): bool => filled($get('discountType')))
                                ->columnSpanFull(),

                            Repeater::make('addOns')
                                ->label('Add-ons')
                                ->schema([
                                    TextInput::make('name')
                                        ->label('Add-on')
                                        ->required(),

                                    TextInput::make('price')
                                        ->label('Price')
                                        ->numeric()
                                        ->prefix($this->getCurrencySymbol())
                                        ->step(0.01)
                                        ->default(0)
                                        ->required(),
                                ])
                                ->addActionLabel('Add add-on')
                                ->reorderable(false)
                                ->defaultItems(0)
                                ->columns(2),
                        ])
                        ->collapsed()
                        ->columnSpanFull(),
                ])
                ->action(function (array $data): void {
                    $this->orderType = (string) ($data['orderType'] ?? $this->orderType);

                    $this->customerId = filled($data['customerId'] ?? null) ? (int) $data['customerId'] : null;
                    $this->customerName = (string) ($data['customerName'] ?? '');

                    $this->tableNumber = $data['tableNumber'] ?? null;
                    $this->notes = (string) ($data['notes'] ?? '');

                    $this->paymentTiming = match ($this->orderType) {
                        'dine-in' => (string) ($data['paymentTiming'] ?? $this->paymentTiming ?? 'pay_later'),
                        default => 'pay_now',
                    };

                    $this->paymentMethod = match ($this->orderType) {
                        'delivery' => (string) ($data['deliveryProvider'] ?? $data['paymentMethod'] ?? $this->paymentMethod ?? 'grab'),
                        default => (string) ($data['paymentMethod'] ?? $this->paymentMethod ?? 'cash'),
                    };

                    $this->discountType = filled($data['discountType'] ?? null) ? (string) $data['discountType'] : null;
                    $this->discountValue = filled($data['discountValue'] ?? null) ? (float) $data['discountValue'] : null;
                    $this->addOns = $data['addOns'] ?? [];
                    $this->creationDate = $data['creationDate'] ?? now()->toDateTimeString();

                    if ($this->paymentTiming !== 'pay_now' || $this->paymentMethod !== 'cash') {
                        $this->paidAmount = 0.0;
                    } elseif (! $this->isTabletMode) {
                        $this->paidAmount = filled($data['paidAmount'] ?? null) ? (float) $data['paidAmount'] : 0.0;
                    }

                    $this->calculateTotals();

                    $this->createOrder();
                })
                ->modalWidth('7xl')
                ->modalHeading('Confirm & Send Order to Kitchen')
                ->modalSubmitActionLabel('Confirm & Send')
                ->modalCancelActionLabel('Back')
                ->visible(fn (): bool => $this->cartItems !== []),
        ];
    }

    private function recalculateItemDiscount(int $index): void
    {
        if (! isset($this->cartItems[$index])) {
            return;
        }

        $subtotal = (float) $this->cartItems[$index]['subtotal'];
        $discountPercentage = $this->cartItems[$index]['discount_percentage'] ?? 0;

        $discountAmount = $discountPercentage > 0 ? ($subtotal * $discountPercentage / 100) : 0;

        $this->cartItems[$index]['discount_amount'] = $discountAmount;
    }

    private function calculateTotals(): void
    {
        // Calculate original subtotal (before any item-level discounts)
        $originalSubtotal = 0.0;
        foreach ($this->cartItems as $item) {
            $originalSubtotal += (float) $item['subtotal'];
        }

        // Calculate item-level discounts
        $itemDiscountTotal = 0.0;
        foreach ($this->cartItems as $item) {
            $itemSubtotal = (float) $item['subtotal'];
            $itemDiscountPercentage = $item['discount_percentage'] ?? 0;
            if ($itemDiscountPercentage > 0) {
                $itemDiscountAmount = $itemSubtotal * ($itemDiscountPercentage / 100);
                $itemDiscountTotal += $itemDiscountAmount;
            }
        }

        // Calculate order-level subtotal (after item discounts)
        $this->totalAmount = $originalSubtotal - $itemDiscountTotal;

        // Calculate final total with order-level discounts and add-ons
        $orderDiscountAmount = 0.0;
        if (! in_array($this->discountType, [null, '', '0'], true) && ! empty($this->discountValue)) {
            $orderDiscountAmount = $originalSubtotal * ($this->discountValue / 100);
        }

        $addOnsTotal = 0.0;
        foreach ($this->addOns as $addOn) {
            if (! empty($addOn['price'])) {
                $addOnsTotal += (float) $addOn['price'];
            }
        }

        $finalTotal = $originalSubtotal - $itemDiscountTotal - $orderDiscountAmount + $addOnsTotal;

        // Calculate change based on final total
        $this->changeAmount = $this->paidAmount - $finalTotal;
    }
}
