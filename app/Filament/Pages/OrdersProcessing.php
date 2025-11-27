<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\Currency;
use App\Enums\DiscountType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\GeneralSettingsService;
use App\Services\OrderCancellationService;
use App\Services\OrderProcessingService;
use App\Services\PosService;
use App\Services\RefundService;
use BackedEnum;
use Exception;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use JaOcero\RadioDeck\Forms\Components\RadioDeck;
use UnitEnum;

final class OrdersProcessing extends Page
{
    public string $statusFilter = 'all';

    public string $paymentStatusFilter = 'all';

    public bool $isTabletMode = true;

    public Currency $currency;

    public array $cartItems = [];

    public string $search = '';

    public ?int $selectedCategoryId = null;

    public string $cancelOrderPin = '';

    public ?int $cancelOrderId = null;

    public string $cancelOrderReason = '';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    // protected static UnitEnum|string|null $navigationGroup = 'Operations';

    protected string $view = 'filament.pages.orders-processing';

    protected static ?string $navigationLabel = 'Orders Processing';

    protected static ?string $title = 'Orders Processing';

    protected static ?int $navigationSort = 2;

    private GeneralSettingsService $settingsService;

    private OrderProcessingService $orderProcessingService;

    private PosService $posService;

    public function boot(GeneralSettingsService $settingsService, OrderProcessingService $orderProcessingService, PosService $posService): void
    {
        $this->settingsService = $settingsService;
        $this->orderProcessingService = $orderProcessingService;
        $this->posService = $posService;

        // Initialize currency from settings
        $currencyCode = $this->settingsService->getCurrency();
        $this->currency = Currency::from($currencyCode);
    }

    public function mount(): void
    {
        // Load tablet mode preference from session
        $this->isTabletMode = session('pos_tablet_mode', true);
    }

    public function getOrders()
    {
        \Illuminate\Support\Facades\Log::info('OrdersProcessing: Loading orders', [
            'status_filter' => $this->statusFilter,
            'payment_status_filter' => $this->paymentStatusFilter,
        ]);

        $query = Order::with(['items.product', 'items.variant', 'customer'])
            ->latest();

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->paymentStatusFilter === 'refunded') {
            $query->whereIn('payment_status', ['refunded', 'refund_partial']);
        } elseif ($this->paymentStatusFilter === 'cancelled') {
            $query->where('status', 'cancelled');
        }

        $orders = $query->get();

        // Log order items with discount information
        foreach ($orders as $order) {
            $itemsWithDiscount = $order->items->filter(function ($item) {
                return ($item->discount_amount ?? 0) > 0 || ($item->discount_percentage ?? 0) > 0;
            });

            if ($itemsWithDiscount->isNotEmpty()) {
                \Illuminate\Support\Facades\Log::info('OrdersProcessing: Order with discounted items loaded', [
                    'order_id' => $order->id,
                    'items_with_discount' => $itemsWithDiscount->map(function ($item) {
                        return [
                            'item_id' => $item->id,
                            'product_id' => $item->product_id,
                            'product_name' => $item->product->name ?? 'Unknown',
                            'subtotal' => $item->subtotal,
                            'discount_percentage' => $item->discount_percentage,
                            'discount_amount' => $item->discount_amount,
                            'discount' => $item->discount,
                        ];
                    })->toArray(),
                ]);
            }
        }

        return $orders;
    }

    public function filterByStatus(string $status): void
    {
        $this->statusFilter = $status;
        $this->paymentStatusFilter = 'all';
    }

    public function filterByPaymentStatus(string $paymentStatus): void
    {
        $this->paymentStatusFilter = $paymentStatus;
        $this->statusFilter = 'all';
    }

    public function toggleServed(int $itemId): void
    {
        try {
            DB::beginTransaction();

            $item = OrderItem::with('order.items')->findOrFail($itemId);
            $wasServed = $item->is_served;
            $item->update(['is_served' => ! $item->is_served]);

            // Check if all items in the order are served
            // Reload the order with fresh items to get the updated is_served status
            $order = Order::with('items')->findOrFail($item->order_id);
            $allItemsServed = $order->items()->where('is_served', false)->count() === 0;

            // Update order status based on item completion
            if ($allItemsServed && $order->items()->count() > 0) {
                // Process inventory deduction when all items are marked as served
                \Illuminate\Support\Facades\Log::info('All items served, attempting to process inventory', [
                    'order_id' => $order->id,
                    'item_id' => $itemId,
                    'already_processed' => $order->inventory_processed,
                ]);

                $inventoryProcessed = $this->orderProcessingService->processOrder($order);

                if (! $inventoryProcessed) {
                    DB::rollBack();

                    \Illuminate\Support\Facades\Log::error('Inventory processing failed - insufficient stock', [
                        'order_id' => $order->id,
                        'item_id' => $itemId,
                    ]);

                    Notification::make()
                        ->danger()
                        ->title('Insufficient Inventory')
                        ->body("Cannot complete order #{$order->id} due to insufficient stock. Please restock ingredients.")
                        ->persistent()
                        ->send();

                    return;
                }

                // Update order status to completed only after successful inventory processing
                $order->update(['status' => 'completed']);

                \Illuminate\Support\Facades\Log::info('Order completed successfully', [
                    'order_id' => $order->id,
                    'inventory_was_processed' => $order->inventory_processed,
                ]);

                DB::commit();

                Notification::make()
                    ->success()
                    ->title('Order Completed')
                    ->body("Order #{$order->id} has been completed and inventory has been updated.")
                    ->send();
            } else {
                // If any item is not served, set order back to pending
                if ($order->status === 'completed') {
                    $order->update(['status' => 'pending']);

                    \Illuminate\Support\Facades\Log::info('Order status reverted to pending', [
                        'order_id' => $order->id,
                        'reason' => 'Item marked as not served',
                    ]);
                }

                DB::commit();

                Notification::make()
                    ->success()
                    ->title('Item Status Updated')
                    ->body($item->is_served ? 'Item marked as served' : 'Item marked as not served')
                    ->send();
            }

            $this->dispatch('$refresh');
        } catch (Exception $e) {
            DB::rollBack();

            \Illuminate\Support\Facades\Log::error('Error in toggleServed', [
                'item_id' => $itemId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Notification::make()
                ->danger()
                ->title('Error')
                ->body('An error occurred: '.$e->getMessage())
                ->persistent()
                ->send();
        }
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

    /**
     * Format amount with currency symbol
     */
    public function formatCurrency(float|int|string $amount): string
    {
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

    public function collectPaymentAction(): Actions\Action
    {
        return Actions\Action::make('collectPayment')
            ->modalHeading(fn (array $arguments) => 'Collect Payment - Order #'.$arguments['orderId'])
            ->modalWidth('lg')
            ->fillForm(function (array $arguments): array {
                $order = Order::find($arguments['orderId']);

                return [
                    'orderId' => $arguments['orderId'],
                    'total' => (float) $order->total,
                ];
            })
            ->form([
                Forms\Components\Hidden::make('orderId'),

                RadioDeck::make('paymentMethod')
                    ->label('Payment Method')
                    ->options(function ($get) {
                        $order = Order::find($get('orderId'));

                        if ($order && $order->order_type === 'delivery') {
                            // Delivery orders show delivery partners
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
                    ->descriptions(function ($get) {
                        $order = Order::find($get('orderId'));

                        if ($order && $order->order_type === 'delivery') {
                            return [
                                'grab' => 'Payment via Grab',
                                'food_panda' => 'Payment via Food Panda',
                            ];
                        }

                        return [
                            'cash' => 'Cash payment',
                            'gcash' => 'Gcash mobile payment',
                            'maya' => 'Maya mobile payment',
                            'bank_transfer' => 'Bank transfer',
                        ];
                    })
                    ->icons(function ($get) {
                        $order = Order::find($get('orderId'));

                        if ($order && $order->order_type === 'delivery') {
                            return [
                                'grab' => 'heroicon-o-device-phone-mobile',
                                'food_panda' => 'heroicon-o-device-phone-mobile',
                            ];
                        }

                        return [
                            'cash' => 'heroicon-o-banknotes',
                            'gcash' => 'heroicon-o-device-phone-mobile',
                            'maya' => 'heroicon-o-device-phone-mobile',
                            'bank_transfer' => 'heroicon-o-building-office',
                        ];
                    })
                    ->default(function ($get) {
                        $order = Order::find($get('orderId'));

                        return ($order && $order->order_type === 'delivery') ? 'grab' : 'cash';
                    })
                    ->required()
                    ->reactive()
                    ->columns(function ($get) {
                        $order = Order::find($get('orderId'));
                        if ($order && $order->order_type === 'delivery') {
                            return 2; // Show delivery partners in 2 columns
                        }

                        return 2; // Show standard payment methods in 2x2 grid

                    })
                    ->color('primary'),

                Section::make('Order Summary')
                    ->schema([
                        Forms\Components\Placeholder::make('order_details')
                            ->label('')
                            ->content(function ($get) {
                                $order = Order::find($get('orderId'));
                                $subtotal = (float) $order->subtotal ?? (float) $order->total;
                                $existingDiscount = (float) ($order->discount_amount ?? 0);
                                $existingAddOns = (float) ($order->add_ons_total ?? 0);
                                $discountAmount = $existingDiscount;

                                if ($get('discountType') && $get('discountValue')) {
                                    // All discounts are percentage-based
                                    $discountAmount = $subtotal * ((float) $get('discountValue') / 100);
                                }

                                $total = $subtotal - $discountAmount + $existingAddOns;

                                $subtotalFormatted = $this->formatCurrency($subtotal);
                                $totalFormatted = $this->formatCurrency($total);
                                $discountFormatted = $this->formatCurrency($discountAmount);
                                $addOnsFormatted = $this->formatCurrency($existingAddOns);

                                $discountHtml = $discountAmount > 0 ? "
                                    <div class='flex justify-between text-sm text-green-600'>
                                        <span>Discount:</span>
                                        <span class='font-medium'>- {$discountFormatted}</span>
                                    </div>
                                " : '';

                                $addOnsHtml = $existingAddOns > 0 ? "
                                    <div class='flex justify-between text-sm text-blue-600'>
                                        <span>Add-ons:</span>
                                        <span class='font-medium'>+ {$addOnsFormatted}</span>
                                    </div>
                                " : '';

                                return new HtmlString("
                                    <div class='space-y-2 p-4 bg-gray-50 rounded-lg'>
                                        <div class='flex justify-between text-sm'>
                                            <span class='text-gray-600'>Subtotal:</span>
                                            <span class='font-medium'>{$subtotalFormatted}</span>
                                        </div>
                                        {$discountHtml}
                                        {$addOnsHtml}
                                        <div class='flex justify-between text-xl font-bold border-t border-gray-300 pt-2'>
                                            <span>Total:</span>
                                            <span class='text-orange-600'>{$totalFormatted}</span>
                                        </div>
                                    </div>
                                ");
                            }),
                    ]),

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
                            ->live()
                            ->minValue(0)
                            ->maxValue(100)
                            ->helperText('Enter percentage (0-100)')
                            ->dehydrated(true),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->visible(function ($get) {
                        // Show discount section for all order types
                        return true;
                    }),

                Section::make('Payment Details')
                    ->schema([
                        // Hidden field to store the amount (always present)
                        Forms\Components\Hidden::make('paidAmount')
                            ->default(0),

                        // Slide-over Numpad for Tablet Mode
                        View::make('filament.components.numpad-slideover')
                            ->viewData(function ($get) {
                                $order = Order::find($get('orderId'));

                                return [
                                    'orderId' => $get('orderId'),
                                    'order' => $order,
                                    'currency' => $this->getCurrencySymbol(),
                                ];
                            })
                            ->visible(fn ($get) => $get('paymentMethod') === 'cash' && $this->isTabletMode),

                        // Regular Input for Desktop Mode
                        Forms\Components\TextInput::make('paidAmountDesktop')
                            ->label('Cash Received')
                            ->numeric()
                            ->prefix($this->getCurrencySymbol())
                            ->step(0.01)
                            ->default(0)
                            ->required()
                            ->reactive()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $set) {
                                $set('paidAmount', $state);
                            })
                            ->visible(fn ($get) => $get('paymentMethod') === 'cash' && ! $this->isTabletMode),

                        Forms\Components\Placeholder::make('change_display')
                            ->label('Change')
                            ->content(function ($get) {
                                $order = Order::find($get('orderId'));
                                $subtotal = (float) $order->subtotal ?? (float) $order->total;
                                $existingDiscount = (float) ($order->discount_amount ?? 0);
                                $existingAddOns = (float) ($order->add_ons_total ?? 0);
                                $discountAmount = $existingDiscount;

                                if ($get('discountType') && $get('discountValue')) {
                                    // All discounts are percentage-based
                                    $discountAmount = $subtotal * ((float) $get('discountValue') / 100);
                                }

                                $total = $subtotal - $discountAmount + $existingAddOns;
                                $paidAmount = (float) ($get('paidAmount') ?? $get('paidAmountDesktop') ?? 0);
                                $change = max(0.0, $paidAmount - $total);

                                return new HtmlString('<div class="text-2xl font-bold text-green-600">'.$this->formatCurrency($change).'</div>');
                            })
                            ->visible(fn ($get) => $get('paymentMethod') === 'cash' && ! $this->isTabletMode && (float) ($get('paidAmountDesktop') ?? 0) > 0),
                    ]),
            ])
            ->action(function (array $data) {
                try {
                    DB::beginTransaction();

                    $order = Order::findOrFail($data['orderId']);

                    \Illuminate\Support\Facades\Log::info('Processing payment collection', [
                        'order_id' => $order->id,
                        'payment_method' => $data['paymentMethod'],
                    ]);

                    // Calculate discount
                    $subtotal = (float) ($order->subtotal ?? $order->total);
                    $existingAddOns = (float) ($order->add_ons_total ?? 0);
                    $discountAmount = 0;

                    if (! empty($data['discountType'])) {
                        $discountType = DiscountType::from($data['discountType']);
                        $discountPercentage = $discountType->getPercentage();

                        // If discount type requires custom value, use the provided value
                        if ($discountType->requiresCustomValue() && ! empty($data['discountValue'])) {
                            $discountPercentage = (float) $data['discountValue'];
                        }

                        // Apply discount if we have a percentage
                        if ($discountPercentage !== null) {
                            $discountAmount = $subtotal * ($discountPercentage / 100);
                        }
                    }

                    $finalTotal = $subtotal - $discountAmount + $existingAddOns;
                    $changeAmount = 0;
                    $paidAmount = 0;

                    // Validate cash payment
                    if ($data['paymentMethod'] === 'cash') {
                        $paidAmount = (float) ($data['paidAmount'] ?? 0);
                        if ($paidAmount < $finalTotal) {
                            \Illuminate\Support\Facades\Log::warning('Insufficient cash payment', [
                                'order_id' => $order->id,
                                'paid_amount' => $paidAmount,
                                'final_total' => $finalTotal,
                            ]);

                            Notification::make()
                                ->danger()
                                ->title('Insufficient Payment')
                                ->body("Cash received ({$this->formatCurrency($paidAmount)}) is less than the total amount ({$this->formatCurrency($finalTotal)})")
                                ->send();

                            DB::rollBack();

                            return;
                        }

                        $changeAmount = $paidAmount - $finalTotal;
                    }

                    // Process inventory deduction when payment is collected
                    $inventoryProcessed = $this->orderProcessingService->processOrder($order);

                    if (! $inventoryProcessed) {
                        DB::rollBack();

                        \Illuminate\Support\Facades\Log::error('Inventory processing failed during payment', [
                            'order_id' => $order->id,
                        ]);

                        Notification::make()
                            ->danger()
                            ->title('Insufficient Inventory')
                            ->body('Cannot complete payment due to insufficient stock. Please check the logs or restock ingredients.')
                            ->persistent()
                            ->send();

                        return;
                    }

                    $order->update([
                        'status' => 'completed',
                        'payment_status' => 'paid',
                        'payment_method' => $data['paymentMethod'],
                        'subtotal' => $subtotal,
                        'discount_type' => $data['discountType'] ?? null,
                        'discount_value' => $data['discountValue'] ?? null,
                        'discount_amount' => $discountAmount,
                        'total' => $finalTotal,
                        'paid_amount' => $paidAmount,
                        'change_amount' => $changeAmount,
                    ]);

                    DB::commit();

                    \Illuminate\Support\Facades\Log::info('Payment collected successfully', [
                        'order_id' => $order->id,
                        'total' => $finalTotal,
                    ]);

                    Notification::make()
                        ->success()
                        ->title('Payment Collected')
                        ->body("Order #{$order->id} completed. Total: ".$this->formatCurrency($finalTotal))
                        ->send();

                    $this->dispatch('$refresh');

                    // Dispatch event to refresh sales data across all components
                    $this->dispatch('payment-collected', [
                        'order_id' => $order->id,
                        'total' => $finalTotal,
                    ]);
                } catch (Exception $e) {
                    DB::rollBack();

                    \Illuminate\Support\Facades\Log::error('Error during payment collection', [
                        'order_id' => $data['orderId'] ?? null,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    Notification::make()
                        ->danger()
                        ->title('Error')
                        ->body('An error occurred: '.$e->getMessage())
                        ->persistent()
                        ->send();
                }
            })
            ->modalSubmitActionLabel('Complete Order');
    }

    public function printKitchenTicket(int $orderId): void
    {
        $order = Order::findOrFail($orderId);

        Notification::make()
            ->success()
            ->title('Printing')
            ->body("Kitchen ticket for order #{$order->id} sent to printer")
            ->send();
    }

    public function addProductAction(): Actions\Action
    {
        return Actions\Action::make('addProduct')
            ->modalHeading(fn (array $arguments) => 'Add Products - Order #'.$arguments['orderId'])
            ->modalWidth('6xl')
            ->modalFooterActionsAlignment('right')
            ->fillForm(function (array $arguments): array {
                // Reset cart when opening the modal
                $this->cartItems = [];
                $this->selectedCategoryId = null;
                $this->search = '';

                $order = Order::with('items.product', 'items.variant')->find($arguments['orderId']);

                return [
                    'orderId' => $arguments['orderId'],
                    'order' => $order,
                ];
            })
            ->form([
                Forms\Components\Hidden::make('orderId'),

                // Main content as raw HTML for two-column layout
                Forms\Components\Placeholder::make('modal_content')
                    ->label('')
                    ->content(function () {
                        return new HtmlString("
                            <div class='grid grid-cols-3 gap-6 h-full'>
                                <!-- Left Column: Products -->
                                <div class='col-span-2'>
                                    <div class='space-y-4'>
                                        <div class='flex gap-2'>
                                            <div class='flex-1'>
                                                <input
                                                    type='text'
                                                    wire:model.live='search'
                                                    placeholder='Search products...'
                                                    class='w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500'
                                                />
                                            </div>
                                            <select
                                                wire:model.live='selectedCategoryId'
                                                class='px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500'
                                            >
                                                <option value=''>All Categories</option>
                                                ".$this->getCategoryOptions()."
                                            </select>
                                        </div>

                                        <div class='grid grid-cols-2 gap-2 max-h-96 overflow-y-auto pr-2'>
                                            ".$this->getProductsHtml()."
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Column: Cart Summary -->
                                <div class='col-span-1'>
                                    <div class='bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-4 h-full flex flex-col'>
                                        <h3 class='text-sm font-semibold text-gray-700 mb-3 uppercase tracking-wide'>Order Items</h3>
                                        
                                        <div class='flex-1 overflow-y-auto space-y-2 mb-4'>
                                            ".($this->getCartItemsHtml() ?: "<p class='text-xs text-gray-500 text-center py-8'>No items added</p>")."
                                        </div>

                                        <div class='border-t border-gray-200 pt-3'>
                                            ".$this->getCartTotalHtml().'
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ');
                    }),

                Forms\Components\Hidden::make('items')
                    ->default(fn () => json_encode($this->cartItems))
                    ->reactive()
                    ->live(),
            ])
            ->action(function (array $data) {
                $orderId = $data['orderId'];
                $items = $this->cartItems;

                if (empty($items)) {
                    Notification::make()
                        ->warning()
                        ->title('No Items')
                        ->body('Please add at least one item to the order')
                        ->send();

                    return;
                }

                // Convert items to the format expected by the service
                $itemsToAdd = [];
                foreach ($items as $item) {
                    if (! empty($item['product_id']) && ! empty($item['quantity'])) {
                        $itemsToAdd[] = [
                            'product_id' => (int) $item['product_id'],
                            'variant_id' => ! empty($item['variant_id']) ? (int) $item['variant_id'] : null,
                            'quantity' => (int) $item['quantity'],
                        ];
                    }
                }

                if (empty($itemsToAdd)) {
                    Notification::make()
                        ->warning()
                        ->title('Invalid Items')
                        ->body('Please check the items you want to add')
                        ->send();

                    return;
                }

                $order = Order::findOrFail($orderId);
                $orderModificationService = app(\App\Services\OrderModificationService::class);

                $result = $orderModificationService->addProductsToOrder($order, $itemsToAdd);

                if ($result['success']) {
                    Notification::make()
                        ->success()
                        ->title('Products Added')
                        ->body($result['message'])
                        ->send();

                    // Reset cart items
                    $this->resetCart();
                    $this->dispatch('$refresh');
                } else {
                    Notification::make()
                        ->danger()
                        ->title('Error')
                        ->body($result['message'])
                        ->persistent()
                        ->send();
                }
            })
            ->modalSubmitActionLabel('Add Products to Order');
    }

    public function addToCart(int $productId, string $productName, float $price, ?int $variantId = null, ?string $variantName = null): void
    {
        $existingIndex = array_search(
            array_filter(
                $this->cartItems,
                fn ($item) => $item['product_id'] === $productId && $item['variant_id'] === $variantId
            ),
            $this->cartItems,
            true
        );

        if ($existingIndex !== false) {
            $this->cartItems[$existingIndex]['quantity'] += 1;
        } else {
            $this->cartItems[] = [
                'product_id' => $productId,
                'product_name' => $productName,
                'variant_id' => $variantId,
                'variant_name' => $variantName,
                'price' => $price,
                'quantity' => 1,
            ];
        }
    }

    public function removeFromCart(int $productId, ?int $variantId = null): void
    {
        $this->cartItems = array_values(
            array_filter(
                $this->cartItems,
                fn ($item) => ! ($item['product_id'] === $productId && $item['variant_id'] === $variantId)
            )
        );
    }

    public function updateQuantity(int $productId, int $quantity, ?int $variantId = null): void
    {
        $item = array_search(
            array_filter(
                $this->cartItems,
                fn ($item) => $item['product_id'] === $productId && $item['variant_id'] === $variantId
            ),
            $this->cartItems,
            true
        );

        if ($item !== false && $quantity > 0) {
            $this->cartItems[$item]['quantity'] = $quantity;
        }
    }

    /**
     * Check if cancel button should be shown for an order
     */
    public function canShowCancel(Order $order): bool
    {
        $cancellationService = app(OrderCancellationService::class);

        return $cancellationService->canCancelOrder($order);
    }

    /**
     * Check if refund button should be shown for an order
     */
    public function canShowRefund(Order $order): bool
    {
        $refundService = app(RefundService::class);

        return $refundService->canShowRefundButton($order);
    }

    /**
     * Get refund button label based on refund type
     */
    public function getRefundLabel(Order $order): string
    {
        $refundService = app(RefundService::class);
        $refundData = $refundService->getRefundableItems($order);

        return $refundData['type'] === 'full' ? 'Refund' : 'Cancel Unpaid';
    }

    public function openCancelModal(int $orderId): void
    {
        $this->cancelOrderId = $orderId;
        $this->cancelOrderPin = '';
        $this->cancelOrderReason = '';
        $this->dispatch('open-cancel-modal');
    }

    public function submitCancelOrder(): void
    {
        if (! $this->cancelOrderId) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('No order selected')
                ->send();

            return;
        }

        $order = Order::findOrFail($this->cancelOrderId);
        $cancellationService = app(OrderCancellationService::class);

        $result = $cancellationService->processCancellation(
            $order,
            Auth::user(),
            $this->cancelOrderPin,
            ! empty($this->cancelOrderReason) ? $this->cancelOrderReason : null
        );

        if ($result['success']) {
            Notification::make()
                ->success()
                ->title('Order Cancelled')
                ->body($result['message'])
                ->send();

            $this->cancelOrderPin = '';
            $this->cancelOrderReason = '';
            $this->cancelOrderId = null;
            $this->dispatch('close-cancel-modal');
            $this->dispatch('$refresh');
        } else {
            Notification::make()
                ->danger()
                ->title('Cancellation Failed')
                ->body($result['message'])
                ->send();
        }
    }

    public function refundAction(): Actions\Action
    {
        return Actions\Action::make('refund')
            ->modalHeading(fn (array $arguments) => 'Refund Order #'.$arguments['orderId'])
            ->modalWidth('sm')
            ->requiresConfirmation()
            ->fillForm(function (array $arguments): array {
                return [
                    'orderId' => (int) ($arguments['orderId'] ?? 0),
                ];
            })
            ->form([
                Forms\Components\Hidden::make('orderId'),

                Forms\Components\TextInput::make('pin')
                    ->label('Admin PIN')
                    ->password()
                    ->placeholder('Enter your 4-6 digit PIN')
                    ->required()
                    ->length(4, 6),
            ])
            ->action(function (array $data) {
                try {
                    $order = Order::findOrFail($data['orderId']);
                    $refundService = app(RefundService::class);

                    $result = $refundService->processRefund($order, Auth::user(), $data['pin']);

                    if ($result['success']) {
                        Notification::make()
                            ->success()
                            ->title('Refund Processed')
                            ->body($result['message'])
                            ->send();

                        $this->dispatch('$refresh');
                    } else {
                        Notification::make()
                            ->danger()
                            ->title('Refund Failed')
                            ->body($result['message'])
                            ->send();
                    }
                } catch (Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Error processing refund', [
                        'order_id' => $data['orderId'] ?? null,
                        'error' => $e->getMessage(),
                    ]);

                    Notification::make()
                        ->danger()
                        ->title('Error')
                        ->body('An error occurred: '.$e->getMessage())
                        ->send();
                }
            })
            ->modalSubmitActionLabel('Confirm Refund')
            ->icon('heroicon-o-arrow-uturn-left');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('toggleMode')
                ->label($this->isTabletMode ? 'Desktop Mode' : 'Tablet Mode')
                ->icon($this->isTabletMode ? 'heroicon-o-computer-desktop' : 'heroicon-o-device-tablet')
                ->color('gray')
                ->action(fn () => $this->toggleMode()),

            Actions\Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->dispatch('$refresh')),
        ];
    }

    protected function getActions(): array
    {
        return [
            $this->collectPaymentAction(),
            $this->addProductAction(),
        ];
    }

    private function resetCart(): void
    {
        $this->cartItems = [];
        $this->selectedCategoryId = null;
        $this->search = '';
    }

    private function getCategoryOptions(): string
    {
        $categories = $this->posService->getActiveCategories();
        $options = '';
        foreach ($categories as $category) {
            $options .= "<option value='{$category->id}'>{$category->name}</option>";
        }

        return $options;
    }

    private function getProductsHtml(): string
    {
        $products = $this->posService->getFilteredProducts(
            $this->selectedCategoryId,
            $this->search
        )->load('activeVariants');

        if ($products->isEmpty()) {
            return "<p class='col-span-2 text-xs text-gray-500 text-center py-4'>No products found</p>";
        }

        $html = '';
        foreach ($products as $product) {
            if (! $this->posService->canAddToCart($product->id)) {
                continue;
            }

            $html .= "
                <div class='border border-gray-200 rounded-lg p-2 hover:shadow-sm transition-shadow bg-white'>
                    <div class='mb-2'>
                        <p class='text-xs font-semibold text-gray-900'>{$product->name}</p>
                        <p class='text-xs text-gray-600'>{$this->formatCurrency($product->price)}</p>
                    </div>";

            if ($product->hasVariants()) {
                foreach ($product->activeVariants as $variant) {
                    $html .= "
                        <button
                            type='button'
                            wire:click=\"addToCart({$product->id}, '{$product->name}', {$variant->price}, {$variant->id}, '{$variant->name}')\"
                            class='w-full mb-1 px-2 py-1 text-xs bg-blue-500 hover:bg-blue-600 text-white rounded font-medium transition-colors'
                        >
                            {$variant->name} {$this->formatCurrency($variant->price)}
                        </button>";
                }
            } else {
                $html .= "
                    <button
                        type='button'
                        wire:click=\"addToCart({$product->id}, '{$product->name}', {$product->price})\"
                        class='w-full px-2 py-1 text-xs bg-green-500 hover:bg-green-600 text-white rounded font-medium transition-colors'
                    >
                        Add
                    </button>";
            }

            $html .= '</div>';
        }

        return $html;
    }

    private function getCartItemsHtml(): string
    {
        if (empty($this->cartItems)) {
            return '';
        }

        $html = '';
        foreach ($this->cartItems as $item) {
            $quantity = (int) ($item['quantity'] ?? 1);
            $price = (float) ($item['price'] ?? 0);
            $subtotal = $quantity * $price;
            $productName = htmlspecialchars($item['product_name'] ?? '');
            $variantName = ! empty($item['variant_name']) ? " ({$item['variant_name']})" : '';
            $productId = (int) $item['product_id'];
            $variantId = ! empty($item['variant_id']) ? (int) $item['variant_id'] : 'null';

            $html .= "
                <div class='bg-white rounded-lg p-2 border border-gray-200 text-xs'>
                    <div class='flex justify-between items-start mb-2'>
                        <div class='flex-1'>
                            <p class='font-semibold text-gray-900'>{$productName}{$variantName}</p>
                            <p class='text-gray-600'>{$this->formatCurrency($price)} × {$quantity}</p>
                        </div>
                        <p class='font-bold text-gray-900'>{$this->formatCurrency($subtotal)}</p>
                    </div>
                    <div class='flex gap-1 items-center'>
                        <input
                            type='number'
                            value='{$quantity}'
                            min='1'
                            wire:change=\"updateQuantity({$productId}, \$event.target.value, {$variantId})\"
                            class='w-10 px-1 py-0.5 border border-gray-300 rounded text-center text-xs'
                        />
                        <button
                            type='button'
                            wire:click=\"removeFromCart({$productId}, {$variantId})\"
                            class='ml-auto px-2 py-0.5 bg-red-500 hover:bg-red-600 text-white rounded text-xs font-medium transition-colors'
                        >
                            Remove
                        </button>
                    </div>
                </div>";
        }

        return $html;
    }

    private function getCartTotalHtml(): string
    {
        $total = 0;
        foreach ($this->cartItems as $item) {
            $quantity = (int) ($item['quantity'] ?? 1);
            $price = (float) ($item['price'] ?? 0);
            $total += $quantity * $price;
        }

        return "
            <div class='text-sm'>
                <div class='flex justify-between items-center'>
                    <span class='font-semibold text-gray-700'>Subtotal:</span>
                    <span class='font-bold text-gray-900'>{$this->formatCurrency($total)}</span>
                </div>
            </div>";
    }
}
