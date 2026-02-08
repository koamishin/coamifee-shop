<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\Currency;
use App\Enums\DiscountType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\GeneralSettingsService;
use App\Services\OrderCancellationService;
use App\Services\OrderModificationService;
use App\Services\OrderProcessingService;
use App\Services\PosService;
use App\Services\RefundService;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;

final class OrdersProcessing extends Page
{
    public string $statusFilter = 'all';

    public string $paymentStatusFilter = 'all';

    public bool $isTabletMode = false;

    public Currency $currency;

    public array $cartItems = [];

    public string $search = '';

    public ?int $selectedCategoryId = null;

    public string $cancelOrderPin = '';

    public ?int $cancelOrderId = null;

    public string $cancelOrderReason = '';

    public float $cashReceived = 0.0;

    public array $paymentState = [
        'paymentMethod' => 'cash',
        'paidAmount' => 0,
        'orderId' => null,
    ];

    private GeneralSettingsService $settingsService;

    private OrderProcessingService $orderProcessingService;

    private PosService $posService;

    public function processPayment(int $orderId, string $paymentMethod, float $paidAmount): void
    {
        try {
            DB::beginTransaction();

            $order = Order::query()->findOrFail($orderId);

            Log::info('Processing payment collection via custom method', [
                'order_id' => $order->id,
                'payment_method' => $paymentMethod,
                'paid_amount' => $paidAmount,
            ]);

            // Recalculate order total
            $this->recalculateOrderTotal($order);
            $order = $order->fresh();

            $finalTotal = (float) $order->total;
            $subtotal = (float) $order->subtotal;
            $changeAmount = 0;

            // Validate cash payment
            if ($paymentMethod === 'cash') {
                // Use a small epsilon for float comparison
                if ($paidAmount < ($finalTotal - 0.01)) {
                    throw new Exception("Cash received ({$this->formatCurrency($paidAmount)}) is less than the total amount ({$this->formatCurrency($finalTotal)})");
                }
                $changeAmount = $paidAmount - $finalTotal;
            } else {
                // For non-cash, we assume exact payment was verified by cashier
                $paidAmount = $finalTotal;
            }

            // Process inventory
            $inventoryProcessed = $this->orderProcessingService->processOrder($order);

            if (! $inventoryProcessed) {
                throw new Exception('Cannot complete payment due to insufficient stock. Please restock ingredients.');
            }

            $order->update([
                'status' => 'completed',
                'payment_status' => 'paid',
                'payment_method' => $paymentMethod,
                'subtotal' => $subtotal,
                'total' => $finalTotal,
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
            ]);

            DB::commit();

            Notification::make()
                ->success()
                ->title('Payment Collected')
                ->body("Order #{$order->id} completed. Total: ".$this->formatCurrency($finalTotal))
                ->send();

            $this->unmountAction();
            $this->dispatch('$refresh');

            $this->dispatch('payment-collected', [
                'order_id' => $order->id,
                'total' => $finalTotal,
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error during payment collection: '.$e->getMessage());

            Notification::make()
                ->danger()
                ->title('Payment Failed')
                ->body($e->getMessage())
                ->persistent()
                ->send();
        }
    }

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
        $this->isTabletMode = session('pos_tablet_mode', false);
    }

    public function getOrders()
    {
        Log::info('OrdersProcessing: Loading orders', [
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

        // Log order items with discount information and recalculate totals
        foreach ($orders as $order) {
            // Recalculate order total to ensure item discounts are reflected
            $this->recalculateOrderTotal($order);

            $itemsWithDiscount = $order->items->filter(fn (OrderItem $item): bool => ($item->discount_amount ?? 0) > 0 || ($item->discount_percentage ?? 0) > 0);

            if ($itemsWithDiscount->isNotEmpty()) {
                Log::info('OrdersProcessing: Order with discounted items loaded', [
                    'order_id' => $order->id,
                    'items_with_discount' => $itemsWithDiscount->map(fn (OrderItem $item): array => [
                        'item_id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name ?? 'Unknown',
                        'subtotal' => $item->subtotal,
                        'discount_percentage' => $item->discount_percentage,
                        'discount_amount' => $item->discount_amount,
                        'discount' => $item->discount,
                    ])->toArray(),
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
                Log::info('All items served, attempting to process inventory', [
                    'order_id' => $order->id,
                    'item_id' => $itemId,
                    'already_processed' => $order->inventory_processed,
                ]);

                $inventoryProcessed = $this->orderProcessingService->processOrder($order);

                if (! $inventoryProcessed) {
                    DB::rollBack();

                    Log::error('Inventory processing failed - insufficient stock', [
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

                Log::info('Order completed successfully', [
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

                    Log::info('Order status reverted to pending', [
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

            Log::error('Error in toggleServed', [
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

    public function updatedCashReceived(): void
    {
        // Trigger a refresh of the form to recalculate the change display
        $this->dispatch('refresh-change-display');
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

    /**
     * Recalculate and update order total based on item discounts
     */
    public function recalculateOrderTotal(Order $order): void
    {
        $order = $order->fresh('items');

        // Calculate item-level discount total
        $itemLevelDiscountTotal = 0.0;
        foreach ($order->items as $item) {
            $itemLevelDiscountTotal += (float) ($item->discount_amount ?? $item->discount ?? 0);
        }

        // Calculate order total
        $originalSubtotal = (float) $order->subtotal;
        $subtotalAfterItemDiscounts = $originalSubtotal - $itemLevelDiscountTotal;
        $orderLevelDiscount = (float) ($order->discount_amount ?? 0);
        $addOnsTotal = (float) ($order->add_ons_total ?? 0);
        $correctTotal = $subtotalAfterItemDiscounts - $orderLevelDiscount + $addOnsTotal;

        // Update the order if total has changed
        if ((float) $order->total !== $correctTotal) {
            $order->update(['total' => $correctTotal]);

            Log::info('Order total recalculated', [
                'order_id' => $order->id,
                'original_total' => $order->getOriginal('total'),
                'new_total' => $correctTotal,
                'item_discounts' => $itemLevelDiscountTotal,
            ]);
        }
    }

    public function collectPaymentAction(): Action
    {
        return Action::make('collectPayment')
            ->modalHeading(fn (array $arguments): string => 'Collect Payment - Order #'.$arguments['orderId'])
            ->modalWidth('6xl')
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->fillForm(function (array $arguments): void {
                $order = Order::with('items')->find($arguments['orderId']);
                $this->recalculateOrderTotal($order);
                $order = $order->fresh();

                $this->paymentState = [
                    'orderId' => (int) $arguments['orderId'],
                    'paymentMethod' => $order->order_type === 'delivery' ? 'grab' : 'cash',
                    'paidAmount' => 0,
                ];
            })
            ->modalContent(function (array $arguments) {
                $orderId = $arguments['orderId'] ?? null;
                if (! $orderId) {
                    return new HtmlString('<div class="p-4 text-red-500">Error: Order ID not found.</div>');
                }

                $order = Order::with(['items.product', 'items.variant'])->find($orderId);

                return view('filament.pages.orders-processing.payment-modal', [
                    'order' => $order,
                ]);
            });
    }

    public function printKitchenTicket(int $orderId): void
    {
        $order = Order::query()->findOrFail($orderId);

        Notification::make()
            ->success()
            ->title('Printing')
            ->body("Kitchen ticket for order #{$order->id} sent to printer")
            ->send();
    }

    public function addProductAction(): Action
    {
        return Action::make('addProduct')
            ->modalHeading(fn (array $arguments): string => 'Add Products - Order #'.$arguments['orderId'])
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
                Hidden::make('orderId'),

                // Main content as raw HTML for two-column layout
                Placeholder::make('modal_content')
                    ->label('')
                    ->content(fn (): HtmlString => new HtmlString("
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
                        ')),

                Hidden::make('items')
                    ->default(fn () => json_encode($this->cartItems))
                    ->reactive()
                    ->live(),
            ])
            ->action(function (array $data): void {
                $orderId = $data['orderId'];
                $items = $this->cartItems;

                if ($items === []) {
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
                            'variant_id' => empty($item['variant_id']) ? null : (int) $item['variant_id'],
                            'quantity' => (int) $item['quantity'],
                            'discount_type' => $item['discount_type'] ?? null,
                            'discount_percentage' => (float) ($item['discount_percentage'] ?? 0),
                        ];
                    }
                }

                if ($itemsToAdd === []) {
                    Notification::make()
                        ->warning()
                        ->title('Invalid Items')
                        ->body('Please check the items you want to add')
                        ->send();

                    return;
                }

                $order = Order::query()->findOrFail($orderId);
                $orderModificationService = resolve(OrderModificationService::class);

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
                fn (array $item): bool => $item['product_id'] === $productId && $item['variant_id'] === $variantId
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
                'discount_type' => null,
                'discount_percentage' => 0,
                'discount_amount' => 0,
            ];
        }
    }

    public function removeFromCart(int $productId, ?int $variantId = null): void
    {
        $this->cartItems = array_values(
            array_filter(
                $this->cartItems,
                fn (array $item): bool => ! ($item['product_id'] === $productId && $item['variant_id'] === $variantId)
            )
        );
    }

    public function updateQuantity(int $productId, int $quantity, ?int $variantId = null): void
    {
        $item = array_search(
            array_filter(
                $this->cartItems,
                fn (array $item): bool => $item['product_id'] === $productId && $item['variant_id'] === $variantId
            ),
            $this->cartItems,
            true
        );

        if ($item !== false && $quantity > 0) {
            $this->cartItems[$item]['quantity'] = $quantity;
        }
    }

    public function updateCartItemDiscount(int $index, string $discountType): void
    {
        if (! isset($this->cartItems[$index])) {
            return;
        }

        $this->cartItems[$index]['discount_type'] = $discountType === '' || $discountType === '0' ? null : $discountType;

        // Auto-fill percentage if it's a predefined discount type
        if ($discountType !== '' && $discountType !== '0') {
            $discountEnum = DiscountType::tryFrom($discountType);
            if ($discountEnum) {
                $percentage = $discountEnum->getPercentage();
                $this->cartItems[$index]['discount_percentage'] = $percentage;
            }
        } else {
            $this->cartItems[$index]['discount_percentage'] = 0;
            $this->cartItems[$index]['discount_amount'] = 0;
        }
    }

    /**
     * Check if cancel button should be shown for an order
     */
    public function canShowCancel(Order $order): bool
    {
        $cancellationService = resolve(OrderCancellationService::class);

        return $cancellationService->canCancelOrder($order);
    }

    /**
     * Check if refund button should be shown for an order
     */
    public function canShowRefund(Order $order): bool
    {
        $refundService = resolve(RefundService::class);

        return $refundService->canShowRefundButton($order);
    }

    /**
     * Get refund button label based on refund type
     */
    public function getRefundLabel(Order $order): string
    {
        $refundService = resolve(RefundService::class);
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

        $order = Order::query()->findOrFail($this->cancelOrderId);
        $cancellationService = resolve(OrderCancellationService::class);

        $result = $cancellationService->processCancellation(
            $order,
            Auth::user(),
            $this->cancelOrderPin,
            $this->cancelOrderReason === '' || $this->cancelOrderReason === '0' ? null : $this->cancelOrderReason
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

    public function refundAction(): Action
    {
        return Action::make('refund')
            ->modalHeading(fn (array $arguments): string => 'Refund Order #'.$arguments['orderId'])
            ->modalWidth('sm')
            ->requiresConfirmation()
            ->fillForm(fn (array $arguments): array => [
                'orderId' => (int) ($arguments['orderId'] ?? 0),
            ])
            ->form([
                Hidden::make('orderId'),

                TextInput::make('pin')
                    ->label('Admin PIN')
                    ->password()
                    ->placeholder('Enter your 4-6 digit PIN')
                    ->required()
                    ->length(4),
            ])
            ->action(function (array $data): void {
                try {
                    $order = Order::query()->findOrFail($data['orderId']);
                    $refundService = resolve(RefundService::class);

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
                    Log::error('Error processing refund', [
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
            // Actions\Action::make('toggleMode')
            // ->label($this->isTabletMode ? 'Desktop Mode' : 'Tablet Mode')
            // ->icon($this->isTabletMode ? 'heroicon-o-computer-desktop' : 'heroicon-o-device-tablet')
            // ->color('gray')
            // ->action(fn () => $this->toggleMode()),

            Action::make('refresh')
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
        );

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
        if ($this->cartItems === []) {
            return '';
        }

        $html = '';
        $discountOptions = DiscountType::getOptions();
        $discountSelectHtml = "<option value=''>None</option>";
        foreach ($discountOptions as $value => $label) {
            $discountSelectHtml .= "<option value='{$value}'>{$label}</option>";
        }

        foreach ($this->cartItems as $index => $item) {
            $quantity = (int) ($item['quantity'] ?? 1);
            $price = (float) ($item['price'] ?? 0);
            $subtotal = $quantity * $price;
            $productName = htmlspecialchars($item['product_name'] ?? '');
            $variantName = empty($item['variant_name']) ? '' : " ({$item['variant_name']})";
            $productId = (int) $item['product_id'];
            $variantId = empty($item['variant_id']) ? 'null' : (int) $item['variant_id'];
            $currentDiscountType = $item['discount_type'] ?? '';
            $currentDiscountPercentage = (int) ($item['discount_percentage'] ?? 0);
            $discountAmount = $currentDiscountPercentage > 0 ? ($subtotal * $currentDiscountPercentage / 100) : 0;
            $finalSubtotal = $subtotal - $discountAmount;

            $html .= "
                <div class='bg-white rounded-lg p-2 border border-gray-200 text-xs'>
                    <div class='flex justify-between items-start mb-2'>
                        <div class='flex-1'>
                            <p class='font-semibold text-gray-900'>{$productName}{$variantName}</p>
                            <p class='text-gray-600'>{$this->formatCurrency($price)} × {$quantity}</p>
                        </div>
                        <p class='font-bold text-gray-900'>{$this->formatCurrency($finalSubtotal)}</p>
                    </div>
                    <div class='mb-2'>
                        <label class='block text-xs font-medium text-gray-700 mb-1'>Discount</label>
                        <select
                            wire:change=\"updateCartItemDiscount({$index}, \$event.target.value)\"
                            class='w-full px-1.5 py-0.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500'
                        >
                            {$discountSelectHtml}
                        </select>
                        ".($currentDiscountPercentage > 0 ? "<div class='text-xs text-green-600 mt-1'>{$currentDiscountPercentage}% off (-{$this->formatCurrency($discountAmount)})</div>" : '')."
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
        $subtotal = 0;
        $totalDiscount = 0;

        foreach ($this->cartItems as $item) {
            $quantity = (int) ($item['quantity'] ?? 1);
            $price = (float) ($item['price'] ?? 0);
            $itemSubtotal = $quantity * $price;
            $subtotal += $itemSubtotal;

            $discountPercentage = (int) ($item['discount_percentage'] ?? 0);
            if ($discountPercentage > 0) {
                $discountAmount = $itemSubtotal * ($discountPercentage / 100);
                $totalDiscount += $discountAmount;
            }
        }

        $finalTotal = $subtotal - $totalDiscount;

        $html = "
            <div class='text-sm space-y-1'>
                <div class='flex justify-between items-center'>
                    <span class='font-semibold text-gray-700'>Subtotal:</span>
                    <span class='font-bold text-gray-900'>{$this->formatCurrency($subtotal)}</span>
                </div>";

        if ($totalDiscount > 0) {
            $html .= "
                <div class='flex justify-between items-center text-green-600'>
                    <span class='font-semibold'>Discount:</span>
                    <span class='font-bold'>-{$this->formatCurrency($totalDiscount)}</span>
                </div>";
        }

        return $html."
                <div class='flex justify-between items-center border-t border-gray-200 pt-1 mt-1'>
                    <span class='font-bold text-gray-900'>Total:</span>
                    <span class='font-bold text-lg text-orange-600'>{$this->formatCurrency($finalTotal)}</span>
                </div>
            </div>";
    }
}
