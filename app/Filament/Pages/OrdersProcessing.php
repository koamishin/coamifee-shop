<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\Currency;
use App\Enums\DiscountType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\GeneralSettingsService;
use App\Services\OrderProcessingService;
use BackedEnum;
use Exception;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use JaOcero\RadioDeck\Forms\Components\RadioDeck;

final class OrdersProcessing extends Page
{
    public string $statusFilter = 'all';

    public bool $isTabletMode = true;

    public Currency $currency;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected string $view = 'filament.pages.orders-processing';

    protected static ?string $navigationLabel = 'Orders';

    protected static ?string $title = 'Orders Processing';

    protected static ?int $navigationSort = 2;

    private GeneralSettingsService $settingsService;

    private OrderProcessingService $orderProcessingService;

    public function boot(GeneralSettingsService $settingsService, OrderProcessingService $orderProcessingService): void
    {
        $this->settingsService = $settingsService;
        $this->orderProcessingService = $orderProcessingService;

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
        $query = Order::with(['items.product', 'items.variant', 'customer'])
            ->latest();

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        return $query->get();
    }

    public function filterByStatus(string $status): void
    {
        $this->statusFilter = $status;
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
        ];
    }
}
