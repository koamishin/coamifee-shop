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
use Storage;
use UnitEnum;

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

    protected static UnitEnum|string|null $navigationGroup = 'Operations';

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
            $paymentMethod = $this->paymentTiming === 'pay_now' ? $this->paymentMethod : 'cash';

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

    /**
     * Get product image URL from R2
     */
    public function getProductImageUrl(?string $imagePath): ?string
    {
        if (! $imagePath) {
            return null;
        }

        return Storage::disk('r2')->url($imagePath);
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
                ->modalHeading('Confirm Order')
                ->modalWidth('2xl')
                ->form([
                    // Hidden field to track tableNumber in form state
                    Forms\Components\Hidden::make('tableNumber'),

                    // Main 2-Column Layout
                    Forms\Components\Placeholder::make('two_column_layout')
                        ->label('')
                        ->content(function ($get) {
                            // Left Column Content
                            $icon = match($this->orderType) {
                                'dine_in' => '🍽️',
                                'takeaway' => '🛍️',
                                'delivery' => '🚚',
                                default => '📋',
                            };
                            
                            $label = match($this->orderType) {
                                'dine_in' => 'Dine In',
                                'takeaway' => 'Takeaway',
                                'delivery' => 'Delivery',
                                default => 'Unknown',
                            };

                            $tableHtml = '';
                            if ($this->orderType === 'dine_in') {
                                $tables = TableNumber::getOptions();
                                $selectedTable = $get('tableNumber') ?? $this->tableNumber;
                                $selectedTableDisplay = $selectedTable ? "Table Number: <strong class='text-lg text-orange-600'>{$selectedTable}</strong>" : '<span class="text-gray-400">No table selected</span>';
                                $tableHtml = "<div class='mb-6'><label class='text-xs font-semibold text-gray-700 mb-2 block'>Select Table</label><div class='grid grid-cols-6 gap-2 mb-4'>";
                                foreach ($tables as $value => $tableLabel) {
                                    $isSelected = $selectedTable === $value;
                                    $tableHtml .= "
                                        <label wire:click='\$set(\"tableNumber\", \"$value\")' class='cursor-pointer group'>
                                            <input type='radio' name='tableNumber' value='$value' class='hidden'>
                                            <div class='p-3 rounded-lg border-2 transition-all text-center text-xs font-bold " . ($isSelected ? 'border-orange-500 bg-gradient-to-br from-orange-500 to-orange-600 text-white shadow-lg shadow-orange-500/50 scale-105' : 'border-gray-300 bg-white text-gray-700 hover:border-orange-300 hover:bg-orange-50') . "'>
                                                {$tableLabel}
                                            </div>
                                        </label>";
                                }
                                $tableHtml .= "</div><div class='p-3 bg-orange-50 border border-orange-200 rounded-lg text-center'>{$selectedTableDisplay}</div></div>";
                            }

                            return new HtmlString("
                                <div class='grid grid-cols-2 gap-6'>
                                    <!-- Left Column -->
                                    <div class='space-y-4'>
                                        <div>
                                            <label class='text-xs font-semibold text-gray-700 mb-2 block'>Order Type</label>
                                            <div class='inline-block p-4 rounded-lg border-2 border-blue-500 bg-blue-50 w-full text-center'>
                                                <div class='text-3xl mb-2'>{$icon}</div>
                                                <div class='text-sm font-bold text-blue-700'>{$label}</div>
                                            </div>
                                        </div>
                                        {$tableHtml}
                                    </div>

                                    <!-- Right Column -->
                                    <div class='space-y-4'>
                                        <div>
                                            <label class='text-xs font-semibold text-gray-700 mb-2 block'>Customer</label>
                                            <div class='bg-gray-50 p-3 rounded-lg border border-gray-200'>
                                                <input type='hidden' name='customerId' wire:model='customerId'>
                                                <select wire:model='customerId' class='w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500'>
                                                    <option value=''>Walk-in Customer</option>
                                                    " . collect($this->customers)->map(fn($customer) => "<option value='{$customer->id}'>{$customer->name}</option>")->implode('') . "
                                                </select>
                                            </div>
                                        </div>
                                        <div wire:key='customer-name'>
                                            " . (!filled($get('customerId')) ? "
                                            <label class='text-xs font-semibold text-gray-700 mb-2 block'>Customer Name</label>
                                            <input type='text' name='customerName' wire:model='customerName' placeholder='Optional' class='w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500'>
                                            " : "") . "
                                        </div>
                                    </div>
                                </div>
                            ");
                        }),

                    Forms\Components\Textarea::make('notes')
                        ->label('Special Instructions')
                        ->placeholder('e.g., Extra hot, no sugar, allergies...')
                        ->rows(2)
                        ->columnSpanFull(),

                    RadioDeck::make('paymentTiming')
                        ->label('Payment Timing')
                        ->options(function ($get) {
                            $orderType = $get('orderType') ?? $this->orderType;

                            if ($orderType === 'dine_in') {
                                return [
                                    'pay_later' => 'Pay Later',
                                    'pay_now' => 'Pay Now',
                                ];
                            }

                            return [
                                'pay_now' => 'Pay Now',
                            ];
                        })
                        ->descriptions(function ($get) {
                            $orderType = $get('orderType') ?? $this->orderType;

                            if ($orderType === 'dine_in') {
                                return [
                                    'pay_later' => 'After meal',
                                    'pay_now' => 'Immediate',
                                ];
                            }

                            return [
                                'pay_now' => 'Before prep',
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
                        ->color('primary')
                        ->columnSpanFull(),

                    Section::make('Payment Details')
                        ->columns(2)
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
                                ->live()
                                ->columnSpan(1),

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
                                ->columnSpan(1),

                            Forms\Components\Placeholder::make('payment_calculation')
                                ->label('Order Summary')
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
                                        <div class='space-y-2 p-4 bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg border-2 border-blue-300'>
                                            <div class='flex justify-between text-sm'>
                                                <span class='text-gray-700 font-medium'>Subtotal:</span>
                                                <span class='font-semibold text-gray-900'>{$subtotalFormatted}</span>
                                            </div>
                                            {$discountHtml}
                                            {$addOnsHtml}
                                            <div class='flex justify-between text-lg font-bold border-t-2 border-blue-300 pt-2 mt-2'>
                                                <span class='text-gray-900'>Total:</span>
                                                <span class='text-orange-600'>{$totalFormatted}</span>
                                            </div>
                                        </div>
                                    ");
                                })
                                ->columnSpanFull(),

                            Forms\Components\Placeholder::make('changeDisplay')
                                ->label('Change')
                                ->content(function ($get) {
                                    $changeAmount = (float) ($get('changeAmount') ?? 0);
                                    $changeFormatted = $this->formatCurrency(abs($changeAmount));

                                    if ($changeAmount > 0) {
                                        return new HtmlString("
                                            <div class='p-3 bg-green-50 border-2 border-green-300 rounded-lg'>
                                                <span class='text-lg font-bold text-green-700'>Change: {$changeFormatted}</span>
                                            </div>
                                        ");
                                    }
                                    if ($changeAmount < 0) {
                                        return new HtmlString("
                                            <div class='p-3 bg-red-50 border-2 border-red-300 rounded-lg'>
                                                <span class='text-lg font-bold text-red-700'>Insufficient: {$changeFormatted}</span>
                                            </div>
                                        ");
                                    }

                                    return new HtmlString("
                                        <div class='p-3 bg-gray-50 border-2 border-gray-300 rounded-lg'>
                                            <span class='text-lg font-bold text-gray-700'>Exact Amount</span>
                                        </div>
                                    ");
                                })
                                ->visible(function ($get) {
                                    $orderType = $get('orderType') ?? $this->orderType;

                                    return $get('paymentMethod') === 'cash' && ! empty($get('paidAmount')) && $orderType !== 'delivery';
                                })
                                ->columnSpan(1),

                            Forms\Components\Hidden::make('changeAmount'),
                        ])
                        ->visible(function ($get) {
                            return $get('paymentTiming') === 'pay_now';
                        }),

                    Section::make('Discount & Add-ons (Optional)')
                        ->columns(2)
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
                                    } else {
                                        // Clear discount value when no discount type is selected
                                        $set('discountValue', null);
                                    }
                                })
                                ->columnSpan(1),

                            // Display-only field to show the discount percentage
                            Forms\Components\Placeholder::make('discount_display')
                                ->label('Discount Amount')
                                ->content(function ($get) {
                                    if (! empty($get('discountType')) && ! empty($get('discountValue'))) {
                                        return new HtmlString("<div class='p-2 bg-green-50 border border-green-200 rounded text-sm font-semibold text-green-700'>{$get('discountValue')}% discount will be applied</div>");
                                    }

                                    return 'No discount applied';
                                })
                                ->columnSpan(1)
                                ->visible(fn ($get) => ! empty($get('discountType'))),

                            // Hidden field to store the discount value
                            Forms\Components\Hidden::make('discountValue'),
                        ])
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
