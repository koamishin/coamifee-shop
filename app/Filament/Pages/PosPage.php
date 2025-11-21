<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\Currency;
use App\Enums\DiscountType;
use App\Enums\TableNumber;
// use App\Models\Category; // Not used directly, using PosService instead
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
// use App\Models\Product; // Not used directly, using PosService instead
use App\Services\GeneralSettingsService;
use App\Services\PosService;
use BackedEnum;
use Exception;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use JaOcero\RadioDeck\Forms\Components\RadioDeck;
use Livewire\Attributes\Locked;

final class PosPage extends Page
{
    public array $cartItems = [];

    public ?int $selectedCategoryId = null;

    public string $search = '';

    public ?int $customerId = null;

    public string $customerName = '';

    public string $orderType = 'dine_in';

    public ?string $tableNumber = null;

    public string $notes = '';

    public string $paymentTiming = 'pay_later';

    public string $paymentMethod = 'cash';

    public float $totalAmount = 0.0;

    public float $paidAmount = 0.0;

    public float $changeAmount = 0.0;

    public bool $isTabletMode = true;

    public ?string $discountType = null;

    public ?float $discountValue = null;

    public array $addOns = [];

    public ?int $selectedProductForVariant = null;

    public ?int $selectedVariantId = null;

    #[Locked]
    public ?int $currentOrderId = null;

    public Collection $categories;

    public Collection $products;

    public Collection $customers;

    public Currency $currency;

    public array $productAvailability = [];

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'POS System';

    protected static ?string $title = 'Point of Sale';

    protected static ?string $model = Order::class;

    protected string $view = 'filament.pages.pos-page';

    private PosService $posService;

    private GeneralSettingsService $settingsService;

    public function mount(): void
    {
        $this->loadData();

        // Load tablet mode preference from session
        $this->isTabletMode = session('pos_tablet_mode', true);
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
        $this->customers = Customer::orderBy('name')->get();
        $this->refreshProducts();
    }

    public function refreshProducts(): void
    {
        $this->products = $this->posService->getFilteredProducts(
            $this->selectedCategoryId,
            $this->search
        )->load('activeVariants');

        // Update product availability
        $this->productAvailability = $this->posService->updateProductAvailability($this->products);
    }

    public function selectCategory(?int $categoryId): void
    {
        $this->selectedCategoryId = $categoryId;
        $this->refreshProducts();
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
            $variant = $product->activeVariants()->find($variantId);
            if ($variant) {
                $variantName = $variant->name;
                $productPrice = $variant->price;
            }
        }

        // Check for existing item with same product AND variant
        $existingItemKey = null;
        foreach ($this->cartItems as $key => $item) {
            if ($item['product_id'] === $productId && ($item['variant_id'] ?? null) === $variantId) {
                $existingItemKey = $key;
                break;
            }
        }

        if ($existingItemKey !== null) {
            $existingItem = $this->cartItems[$existingItemKey];
            // Check if we can increment quantity
            $newQuantity = $existingItem['quantity'] + 1;
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
            ];
        }

        $this->calculateTotals();
        $this->refreshProducts(); // Refresh availability

        $notificationBody = $displayName ?? $product->name;
        Notification::make()
            ->success()
            ->title('Added to cart')
            ->body("{$notificationBody} added to cart")
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
        $this->refreshProducts(); // Refresh availability
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
            $this->calculateTotals();
            $this->refreshProducts(); // Refresh availability
        }
    }

    public function updatedPaidAmount(): void
    {
        $this->calculateTotals();
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
        if (empty($this->cartItems)) {
            Notification::make()
                ->warning()
                ->title('Cart is empty')
                ->body('Please add items to the cart before creating the order')
                ->send();

            return;
        }

        try {
            DB::beginTransaction();

            // Calculate discount
            $subtotal = $this->totalAmount;
            $discountAmount = 0.0;

            if (! empty($this->discountType) && ! empty($this->discountValue)) {
                // All discounts are percentage-based
                $discountAmount = $subtotal * ($this->discountValue / 100);
            }

            // Calculate add-ons total
            $addOnsTotal = 0.0;
            foreach ($this->addOns as $addOn) {
                if (! empty($addOn['price'])) {
                    $addOnsTotal += (float) $addOn['price'];
                }
            }

            $finalTotal = $subtotal - $discountAmount + $addOnsTotal;

            // Determine payment status and method based on payment timing
            $paymentStatus = $this->paymentTiming === 'pay_now' ? 'paid' : 'unpaid';
            $paymentMethod = $this->paymentTiming === 'pay_now' ? $this->paymentMethod : null;

            // Prepare order data
            $orderData = [
                'customer_id' => $this->customerId,
                'customer_name' => $this->customerName ?: 'Walk-in Customer',
                'order_type' => $this->orderType,
                'table_number' => $this->tableNumber,
                'notes' => $this->notes,
                'subtotal' => $subtotal,
                'discount_type' => $this->discountType,
                'discount_value' => $this->discountValue,
                'discount_amount' => $discountAmount,
                'add_ons' => ! empty($this->addOns) ? $this->addOns : null,
                'add_ons_total' => $addOnsTotal,
                'total' => $finalTotal,
                'status' => 'pending',
                'payment_status' => $paymentStatus,
                'payment_method' => $paymentMethod,
            ];

            // Add paid amount and change if paying now
            if ($this->paymentTiming === 'pay_now' && $this->paidAmount > 0) {
                $orderData['paid_amount'] = $this->paidAmount;
                $orderData['change_amount'] = $this->changeAmount;
            }

            $order = Order::create($orderData);

            foreach ($this->cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['variant_id'] ?? null,
                    'variant_name' => $item['variant_name'] ?? null,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['subtotal'],
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
        $this->orderType = 'dine_in';
        $this->tableNumber = null;
        $this->notes = '';
        $this->paymentTiming = 'pay_later';
        $this->paymentMethod = 'cash';
        $this->paidAmount = 0.0;
        $this->discountType = null;
        $this->discountValue = null;
        $this->addOns = [];
        $this->calculateTotals();
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    /**
     * Format amount with currency symbol
     */
    public function formatCurrency(float $amount): string
    {
        return $this->currency->formatAmount($amount);
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

    protected function getActions(): array
    {
        return [
            Actions\Action::make('newOrder')
                ->label('New Order')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->action(fn () => $this->resetOrder()),

            Actions\Action::make('clearCart')
                ->label('Clear Cart')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->action(fn () => $this->clearCart())
                ->hidden(fn () => empty($this->cartItems)),

            Actions\Action::make('placeOrder')
                ->label('Place Order')
                ->icon('heroicon-o-shopping-bag')
                ->color('success')
                ->modalHeading('Confirm Order Details')
                ->modalWidth('lg')
                ->form([
                    Forms\Components\Select::make('customerId')
                        ->label('Customer')
                        ->options(fn () => $this->customers->pluck('name', 'id')->toArray())
                        ->placeholder('Walk-in Customer')
                        ->searchable()
                        ->native(false),

                    Forms\Components\TextInput::make('customerName')
                        ->label('Customer Name')
                        ->placeholder('Optional for walk-in customers')
                        ->visible(function ($get) {
                            return ! filled($get('customerId'));
                        }),

                    Forms\Components\Select::make('tableNumber')
                        ->label('Table Number')
                        ->options(TableNumber::getOptions())
                        ->placeholder('Select a table')
                        ->required()
                        ->native(false)
                        ->searchable()
                        ->visible(fn () => $this->orderType === 'dine_in'),

                    Forms\Components\Textarea::make('notes')
                        ->label('Special Instructions')
                        ->placeholder('e.g., Extra hot, no sugar, allergies...')
                        ->rows(2),

                    RadioDeck::make('orderType')
                        ->label(fn () => ! empty($this->cartItems) ? 'Order Type (Locked)' : 'Order Type')
                        ->options([
                            'dine_in' => 'Dine In',
                            'takeaway' => 'Takeaway',
                            'delivery' => 'Delivery',
                        ])
                        ->descriptions([
                            'dine_in' => 'Customer will dine at the restaurant',
                            'takeaway' => 'Customer will take the order to go',
                            'delivery' => 'Order will be delivered to customer',
                        ])
                        ->icons([
                            'dine_in' => 'heroicon-o-building-storefront',
                            'takeaway' => 'heroicon-o-shopping-bag',
                            'delivery' => 'heroicon-o-truck',
                        ])
                        ->default($this->orderType)
                        ->required()
                        ->reactive()
                        ->disabled(fn () => ! empty($this->cartItems))
                        ->helperText(fn () => ! empty($this->cartItems) ? 'Order type cannot be changed while items are in cart. Clear the cart to change order type.' : '')
                        ->columns(3)
                        ->color('primary')
                        ->afterStateUpdated(function ($state) {
                            $this->orderType = $state;
                        }),

                    RadioDeck::make('paymentTiming')
                        ->label('Payment Timing')
                        ->options(function ($get) {
                            $orderType = $get('orderType') ?? $this->orderType;

                            if ($orderType === 'dine_in') {
                                return [
                                    'pay_later' => 'Pay Later (After meal is ready)',
                                    'pay_now' => 'Pay Now (Immediate payment)',
                                ];
                            }

                            // Takeaway/Delivery only shows Pay Now
                            return [
                                'pay_now' => 'Pay Now (Immediate payment)',
                            ];

                        })
                        ->descriptions(function ($get) {
                            $orderType = $get('orderType') ?? $this->orderType;

                            if ($orderType === 'dine_in') {
                                return [
                                    'pay_later' => 'Payment will be collected when order is ready',
                                    'pay_now' => 'Customer will pay immediately before order is sent to kitchen',
                                ];
                            }

                            // Takeaway/Delivery description for Pay Now
                            return [
                                'pay_now' => 'Payment is required before order preparation',
                            ];

                        })
                        ->icons(function ($get) {
                            $orderType = $get('orderType') ?? $this->orderType;

                            if ($orderType === 'dine_in') {
                                return [
                                    'pay_later' => 'heroicon-o-clock',
                                    'pay_now' => 'heroicon-o-banknotes',
                                ];
                            }

                            // Takeaway/Delivery only shows Pay Now icon
                            return [
                                'pay_now' => 'heroicon-o-banknotes',
                            ];

                        })
                        ->default(function ($get) {
                            $orderType = $get('orderType') ?? $this->orderType;

                            return $orderType === 'dine_in' ? 'pay_later' : 'pay_now';
                        })
                        ->required()
                        ->reactive()
                        ->live()
                        ->afterStateUpdated(function ($state, $set) {
                            $this->paymentTiming = $state;
                        })
                        ->columns(function ($get) {
                            $orderType = $get('orderType') ?? $this->orderType;

                            return $orderType === 'dine_in' ? 2 : 1;
                        })
                        ->color('primary'),

                    Section::make('Payment Details')
                        ->schema([
                            Forms\Components\Select::make('paymentMethod')
                                ->label('Payment Method')
                                ->searchable(false)
                                ->options(function ($get) {
                                    $orderType = $get('orderType') ?? $this->orderType;

                                    if ($orderType === 'delivery') {
                                        // Delivery only shows delivery partners
                                        return [
                                            'grab' => 'Grab',
                                            'food_panda' => 'Food Panda',
                                        ];
                                    }

                                    // Dine In / Takeaway show standard payment methods
                                    return [
                                        'cash' => 'Cash',
                                        'gcash' => 'Gcash',
                                        'maya' => 'Maya',
                                        'bank_transfer' => 'Bank Transfer',
                                    ];

                                })
                                ->default(function ($get) {
                                    $orderType = $get('orderType') ?? $this->orderType;

                                    return $orderType === 'delivery' ? 'grab' : 'cash';
                                })
                                ->required()
                                ->native(false)
                                ->reactive()
                                ->live(),

                            Forms\Components\Placeholder::make('payment_calculation')
                                ->label('Payment Calculation')
                                ->content(function ($get) {
                                    $subtotal = $this->totalAmount;
                                    $discountAmount = 0.0;

                                    if ($get('discountType') && $get('discountValue')) {
                                        $discountAmount = $subtotal * ((float) $get('discountValue') / 100);
                                    }

                                    $addOnsTotal = 0.0;
                                    $addOns = $get('addOns') ?? [];
                                    foreach ($addOns as $addOn) {
                                        if (! empty($addOn['price'])) {
                                            $addOnsTotal += (float) $addOn['price'];
                                        }
                                    }

                                    $total = $subtotal - $discountAmount + $addOnsTotal;

                                    $subtotalFormatted = $this->formatCurrency($subtotal);
                                    $totalFormatted = $this->formatCurrency($total);
                                    $discountFormatted = $this->formatCurrency($discountAmount);
                                    $addOnsTotalFormatted = $this->formatCurrency($addOnsTotal);

                                    $discountHtml = $discountAmount > 0 ? "
                                        <div class='flex justify-between text-sm text-green-600'>
                                            <span>Discount:</span>
                                            <span class='font-medium'>- {$discountFormatted}</span>
                                        </div>
                                    " : '';

                                    $addOnsHtml = $addOnsTotal > 0 ? "
                                        <div class='flex justify-between text-sm text-blue-600'>
                                            <span>Add-ons:</span>
                                            <span class='font-medium'>+ {$addOnsTotalFormatted}</span>
                                        </div>
                                    " : '';

                                    return new HtmlString("
                                        <div class='space-y-2 p-4 bg-gray-50 rounded-lg border border-gray-200'>
                                            <div class='flex justify-between text-sm'>
                                                <span class='text-gray-600'>Subtotal:</span>
                                                <span class='font-medium'>{$subtotalFormatted}</span>
                                            </div>
                                            {$discountHtml}
                                            {$addOnsHtml}
                                            <div class='flex justify-between text-lg font-bold border-t border-gray-300 pt-2 mt-2'>
                                                <span>Total:</span>
                                                <span class='text-orange-600'>{$totalFormatted}</span>
                                            </div>
                                        </div>
                                    ");
                                }),

                            Forms\Components\TextInput::make('paidAmount')
                                ->label('Amount Paid')
                                ->numeric()
                                ->prefix($this->getCurrencySymbol())
                                ->step(0.01)
                                ->required(function ($get) {
                                    return $get('paymentMethod') === 'cash' && ($get('orderType') ?? $this->orderType) !== 'delivery';
                                })
                                ->reactive()
                                ->live()
                                ->visible(function ($get) {
                                    $orderType = $get('orderType') ?? $this->orderType;

                                    return $get('paymentMethod') === 'cash' && $orderType !== 'delivery';
                                })
                                ->afterStateUpdated(function ($state, $set, $get) {
                                    // Auto-set exact amount for delivery orders
                                    $orderType = $get('orderType') ?? $this->orderType;
                                    if ($orderType === 'delivery') {
                                        $subtotal = $this->totalAmount;
                                        $discountAmount = 0.0;

                                        if ($get('discountType') && $get('discountValue')) {
                                            $discountAmount = $subtotal * ((float) $get('discountValue') / 100);
                                        }

                                        $addOnsTotal = 0.0;
                                        $addOns = $get('addOns') ?? [];
                                        foreach ($addOns as $addOn) {
                                            if (! empty($addOn['price'])) {
                                                $addOnsTotal += (float) $addOn['price'];
                                            }
                                        }

                                        $total = $subtotal - $discountAmount + $addOnsTotal;
                                        $set('paidAmount', $total);
                                        $set('changeAmount', 0);

                                        return;
                                    }

                                    // Calculate change for cash payments (Dine In / Takeaway)
                                    $subtotal = $this->totalAmount;
                                    $discountAmount = 0.0;

                                    if ($get('discountType') && $get('discountValue')) {
                                        $discountAmount = $subtotal * ((float) $get('discountValue') / 100);
                                    }

                                    $addOnsTotal = 0.0;
                                    $addOns = $get('addOns') ?? [];
                                    foreach ($addOns as $addOn) {
                                        if (! empty($addOn['price'])) {
                                            $addOnsTotal += (float) $addOn['price'];
                                        }
                                    }

                                    $total = $subtotal - $discountAmount + $addOnsTotal;
                                    $paid = (float) $state;
                                    $change = $paid - $total;

                                    $set('changeAmount', $change);
                                })
                                ->helperText(function ($get) {
                                    $orderType = $get('orderType') ?? $this->orderType;

                                    return $orderType === 'delivery' ? 'Exact amount required' : 'Enter the amount received from customer';
                                }),

                            Forms\Components\Placeholder::make('changeDisplay')
                                ->label('Change')
                                ->content(function ($get) {
                                    $changeAmount = (float) ($get('changeAmount') ?? 0);
                                    $changeFormatted = $this->formatCurrency(abs($changeAmount));

                                    if ($changeAmount > 0) {
                                        return new HtmlString("
                                            <div class='p-3 bg-green-50 border border-green-200 rounded-lg'>
                                                <span class='text-lg font-bold text-green-700'>Change: {$changeFormatted}</span>
                                            </div>
                                        ");
                                    }
                                    if ($changeAmount < 0) {
                                        return new HtmlString("
                                            <div class='p-3 bg-red-50 border border-red-200 rounded-lg'>
                                                <span class='text-lg font-bold text-red-700'>Insufficient: {$changeFormatted}</span>
                                            </div>
                                        ");
                                    }

                                    return new HtmlString("
                                        <div class='p-3 bg-gray-50 border border-gray-200 rounded-lg'>
                                            <span class='text-lg font-bold text-gray-700'>Exact Amount</span>
                                        </div>
                                    ");
                                })
                                ->visible(function ($get) {
                                    $orderType = $get('orderType') ?? $this->orderType;

                                    return $get('paymentMethod') === 'cash' && ! empty($get('paidAmount')) && $orderType !== 'delivery';
                                }),

                            Forms\Components\Hidden::make('changeAmount'),
                        ])
                        ->visible(function ($get) {
                            return $get('paymentTiming') === 'pay_now';
                        })
                        ->columns(1),

                    Section::make('Apply Discount (Optional)')
                        ->schema([
                            Forms\Components\Select::make('discountType')
                                ->label('Discount Type')
                                ->options(DiscountType::getOptions())
                                ->placeholder('No discount')
                                ->reactive()
                                ->live()
                                ->afterStateUpdated(function ($state, $set) {
                                    if (! empty($state)) {
                                        $discountType = DiscountType::from($state);
                                        $percentage = $discountType->getPercentage();

                                        if ($percentage !== null) {
                                            $set('discountValue', $percentage);
                                        } else {
                                            $set('discountValue', null);
                                        }
                                    }
                                })
                                ->helperText(function ($state) {
                                    return ! empty($state) ? DiscountType::from($state)->getDescription() : null;
                                }),

                            Forms\Components\TextInput::make('discountValue')
                                ->label('Discount Value')
                                ->numeric()
                                ->suffix('%')
                                ->visible(function ($get) {
                                    return ! empty($get('discountType')) && DiscountType::from($get('discountType'))->requiresCustomValue();
                                })
                                ->reactive()
                                ->minValue(0)
                                ->maxValue(100)
                                ->helperText('Enter percentage (0-100)'),
                        ])
                        ->columns(2)
                        ->collapsible()
                        ->visible(function ($get) {
                            // Hide discount section for Dine In + Pay Later
                            $orderType = $get('orderType') ?? $this->orderType;
                            $paymentTiming = $get('paymentTiming') ?? 'pay_later';

                            // Show discount section unless it's dine_in AND pay_later
                            return ! ($orderType === 'dine_in' && $paymentTiming === 'pay_later');
                        }),

                    Section::make('Add-Ons (Optional)')
                        ->schema([
                            Forms\Components\Repeater::make('addOns')
                                ->label('')
                                ->table([
                                    TableColumn::make('Add-on Name'),
                                    TableColumn::make('Price'),
                                ])
                                ->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->placeholder('e.g., Extra shot, Whipped cream')
                                        ->required(),

                                    Forms\Components\TextInput::make('price')
                                        ->numeric()
                                        ->prefix($this->getCurrencySymbol())
                                        ->step(0.01)
                                        ->default(0)
                                        ->required()
                                        ->reactive(),
                                ])
                                ->addActionLabel('Add another item')
                                ->reorderable(false)
                                ->reactive()
                                ->defaultItems(0),
                        ])

                        ->collapsible(),

                    Forms\Components\Placeholder::make('order_summary')
                        ->label('Order Summary')
                        ->content(function ($get) {
                            $subtotal = $this->totalAmount;
                            $discountAmount = 0.0;

                            if ($get('discountType') && $get('discountValue')) {
                                // All discounts are percentage-based
                                $discountAmount = $subtotal * ((float) $get('discountValue') / 100);
                            }

                            // Calculate add-ons total
                            $addOnsTotal = 0.0;
                            $addOns = $get('addOns') ?? [];
                            foreach ($addOns as $addOn) {
                                if (! empty($addOn['price'])) {
                                    $addOnsTotal += (float) $addOn['price'];
                                }
                            }

                            $total = $subtotal - $discountAmount + $addOnsTotal;
                            $items = count($this->cartItems);

                            $subtotalFormatted = $this->formatCurrency($subtotal);
                            $totalFormatted = $this->formatCurrency($total);
                            $discountFormatted = $this->formatCurrency($discountAmount);
                            $addOnsTotalFormatted = $this->formatCurrency($addOnsTotal);

                            $discountHtml = $discountAmount > 0 ? "
                                <div class='flex justify-between text-sm text-green-600'>
                                    <span>Discount:</span>
                                    <span class='font-medium'>- {$discountFormatted}</span>
                                </div>
                            " : '';

                            $addOnsHtml = $addOnsTotal > 0 ? "
                                <div class='flex justify-between text-sm text-blue-600'>
                                    <span>Add-ons:</span>
                                    <span class='font-medium'>+ {$addOnsTotalFormatted}</span>
                                </div>
                            " : '';

                            $paymentTiming = $get('paymentTiming') ?? 'pay_later';
                            $paymentNoteClass = $paymentTiming === 'pay_now' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-blue-50 border-blue-200 text-blue-700';
                            $paymentNoteText = $paymentTiming === 'pay_now' ? 'Customer will pay immediately' : 'Payment will be collected when order is ready';

                            return new HtmlString("
                                <div class='space-y-2 p-4 bg-gray-50 rounded-lg border border-gray-200'>
                                    <div class='flex justify-between text-sm'>
                                        <span class='text-gray-600'>Items:</span>
                                        <span class='font-semibold'>{$items} item(s)</span>
                                    </div>
                                    <div class='flex justify-between text-sm'>
                                        <span class='text-gray-600'>Subtotal:</span>
                                        <span class='font-medium'>{$subtotalFormatted}</span>
                                    </div>
                                    {$discountHtml}
                                    {$addOnsHtml}
                                    <div class='flex justify-between text-base font-bold border-t border-gray-300 pt-2 mt-2'>
                                        <span>Total:</span>
                                        <span class='text-orange-600'>{$totalFormatted}</span>
                                    </div>
                                    <div class='mt-3 p-2 {$paymentNoteClass} border rounded text-xs'>
                                        <strong>Note:</strong> {$paymentNoteText}
                                    </div>
                                </div>
                            ");
                        }),
                ])
                ->action(function (array $data) {
                    // Update properties from form
                    $this->orderType = $data['orderType'] ?? $this->orderType;
                    $this->customerId = $data['customerId'] ?? null;
                    $this->customerName = $data['customerName'] ?? '';
                    $this->tableNumber = $data['tableNumber'] ?? null;
                    $this->notes = $data['notes'] ?? '';
                    $this->paymentTiming = $data['paymentTiming'] ?? 'pay_later';
                    $this->paymentMethod = $data['paymentMethod'] ?? 'cash';
                    $this->discountType = $data['discountType'] ?? null;
                    $this->discountValue = ! empty($data['discountValue']) ? (float) $data['discountValue'] : null;
                    $this->addOns = $data['addOns'] ?? [];
                    $this->paidAmount = ! empty($data['paidAmount']) ? (float) $data['paidAmount'] : 0.0;
                    $this->changeAmount = ! empty($data['changeAmount']) ? (float) $data['changeAmount'] : 0.0;

                    // Create the order
                    $this->createOrder();
                })
                ->modalWidth(Width::FiveExtraLarge)

                ->modalSubmitActionLabel('Confirm & Send to Kitchen')
                ->visible(fn () => ! empty($this->cartItems)),
        ];
    }

    private function calculateTotals(): void
    {
        $this->totalAmount = collect($this->cartItems)->sum('subtotal');
        $this->changeAmount = $this->paidAmount - $this->totalAmount;
    }
}
