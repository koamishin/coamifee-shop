<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use BackedEnum;
use Exception;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;

final class PosPage extends Page
{
    public array $cartItems = [];

    public ?int $selectedCategoryId = null;

    public ?int $customerId = null;

    public string $customerName = '';

    public string $orderType = 'dine_in';

    public ?string $tableNumber = null;

    public string $notes = '';

    public string $paymentMethod = 'cash';

    public float $totalAmount = 0.0;

    public float $paidAmount = 0.0;

    public float $changeAmount = 0.0;

    #[Locked]
    public ?int $currentOrderId = null;

    public Collection $categories;

    public Collection $products;

    public Collection $customers;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'POS System';

    protected static ?string $title = 'Point of Sale';

    protected static ?string $model = Order::class;

    protected string $view = 'filament.pages.pos-page';

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->categories = Category::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $this->customers = Customer::orderBy('name')->get();
        $this->refreshProducts();
    }

    public function refreshProducts(): void
    {
        $query = Product::where('is_active', true);

        if ($this->selectedCategoryId) {
            $query->where('category_id', $this->selectedCategoryId);
        }

        $this->products = $query->with('category')
            ->orderBy('name')
            ->get();
    }

    public function selectCategory(?int $categoryId): void
    {
        $this->selectedCategoryId = $categoryId;
        $this->refreshProducts();
    }

    public function addToCart(int $productId): void
    {
        $product = Product::find($productId);

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
            $this->cartItems = collect($this->cartItems)
                ->map(function ($item) use ($productId) {
                    if ($item['product_id'] === $productId) {
                        $item['quantity']++;
                        $item['subtotal'] = $item['quantity'] * $item['price'];
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
    }

    public function updateQuantity(int $index, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->removeFromCart($index);

            return;
        }

        if (isset($this->cartItems[$index])) {
            $this->cartItems[$index]['quantity'] = $quantity;
            $this->cartItems[$index]['subtotal'] = $quantity * $this->cartItems[$index]['price'];
            $this->calculateTotals();
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

    public function completeOrder(): void
    {
        if (empty($this->cartItems)) {
            Notification::make()
                ->warning()
                ->title('Cart is empty')
                ->body('Please add items to the cart before completing the order')
                ->send();

            return;
        }

        try {
            DB::beginTransaction();

            $order = Order::create([
                'customer_id' => $this->customerId,
                'customer_name' => $this->customerName ?: 'Walk-in Customer',
                'order_type' => $this->orderType,
                'table_number' => $this->tableNumber,
                'notes' => $this->notes,
                'total' => $this->totalAmount * 1.10, // Include 10% tax
                'status' => 'completed',
                'payment_method' => $this->paymentMethod,
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

            Notification::make()
                ->success()
                ->title('Order completed successfully!')
                ->body("Order #{$order->id} has been processed")
                ->send();

            $this->resetOrder();

        } catch (Exception $e) {
            DB::rollBack();

            Notification::make()
                ->danger()
                ->title('Error processing order')
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
        $this->paymentMethod = 'cash';
        $this->paidAmount = 0.0;
        $this->calculateTotals();
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
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
        ];
    }

    private function calculateTotals(): void
    {
        $this->totalAmount = collect($this->cartItems)->sum('subtotal');
        $this->changeAmount = $this->paidAmount - $this->totalAmount;
    }
}
