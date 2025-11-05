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
        );

        // Update product availability
        $this->productAvailability = $this->posService->updateProductAvailability($this->products);
    }

    public function selectCategory(?int $categoryId): void
    {
        $this->selectedCategoryId = $categoryId;
        $this->refreshProducts();
    }

    public function addToCart(int $productId): void
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

        $existingItem = collect($this->cartItems)
            ->firstWhere('product_id', $productId);

        if ($existingItem) {
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

            $this->cartItems = collect($this->cartItems)
                ->map(function ($item) use ($productId, $newQuantity) {
                    if ($item['product_id'] === $productId) {
                        $item['quantity'] = $newQuantity;
                        $item['subtotal'] = $newQuantity * $item['price'];
                    }

                    return $item;
                })
                ->toArray();
        } else {
            $this->cartItems[] = [
                'product_id' => $productId,
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => 1,
                'subtotal' => $product->price,
            ];
        }

        $this->calculateTotals();
        $this->refreshProducts(); // Refresh availability

        Notification::make()
            ->success()
            ->title('Added to cart')
            ->body("{$product->name} added to cart")
            ->send();
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

            $order = Order::create([
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
            ]);

            foreach ($this->cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
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
                        ->visible(fn ($get) => ! filled($get('customerId'))),

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

                    Forms\Components\Radio::make('paymentTiming')
                        ->label('Payment Timing')
                        ->options([
                            'pay_later' => 'Pay Later (After meal is ready)',
                            'pay_now' => 'Pay Now (Immediate payment)',
                        ])
                        ->default('pay_later')
                        ->inline()
                        ->required()
                        ->reactive()
                        ->helperText(fn ($state) => $state === 'pay_now' ? 'Customer will pay immediately before order is sent to kitchen' : 'Payment will be collected when order is ready'),

                    Section::make('Payment Details')
                        ->schema([
                            Forms\Components\Select::make('paymentMethod')
                                ->label('Payment Method')
                                ->options([
                                    'cash' => 'Cash',
                                    'card' => 'Card',
                                    'gcash' => 'GCash',
                                    'maya' => 'Maya',
                                ])
                                ->default('cash')
                                ->required()
                                ->native(false),
                        ])
                        ->visible(fn ($get) => $get('paymentTiming') === 'pay_now')
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
                                ->helperText(fn ($state) => ! empty($state) ? DiscountType::from($state)->getDescription() : null),

                            Forms\Components\TextInput::make('discountValue')
                                ->label('Discount Value')
                                ->numeric()
                                ->suffix('%')
                                ->visible(fn ($get) => ! empty($get('discountType')) && DiscountType::from($get('discountType'))->requiresCustomValue())
                                ->reactive()
                                ->minValue(0)
                                ->maxValue(100)
                                ->helperText('Enter percentage (0-100)'),
                        ])
                        ->columns(2)
                        ->collapsible(),

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
                    $this->customerId = $data['customerId'] ?? null;
                    $this->customerName = $data['customerName'] ?? '';
                    $this->tableNumber = $data['tableNumber'] ?? null;
                    $this->notes = $data['notes'] ?? '';
                    $this->paymentTiming = $data['paymentTiming'] ?? 'pay_later';
                    $this->paymentMethod = $data['paymentMethod'] ?? 'cash';
                    $this->discountType = $data['discountType'] ?? null;
                    $this->discountValue = ! empty($data['discountValue']) ? (float) $data['discountValue'] : null;
                    $this->addOns = $data['addOns'] ?? [];

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
