<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Schemas;

use App\Filament\Concerns\CurrencyAware;
use App\Models\Product;
use App\Models\ProductVariant;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

final class OrderForm
{
    use CurrencyAware;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order Information')
                    ->description(
                        'Basic order details and customer information',
                    )
                    ->icon('heroicon-o-shopping-cart')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('customer_name')
                                ->label('Customer Name')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('e.g., John Doe')
                                ->helperText('Enter the customer\'s full name')
                                ->columnSpan(1),
                            Select::make('customer_id')
                                ->label('Existing Customer')
                                ->relationship('customer', 'name')
                                ->searchable()
                                ->placeholder('Select an existing customer')
                                ->helperText(
                                    'Link to an existing customer account',
                                )
                                ->columnSpan(1),
                        ]),

                        Grid::make(4)->schema([
                            Select::make('order_type')
                                ->label('Order Type')
                                ->required()
                                ->options([
                                    'dine-in' => 'Dine In',
                                    'takeaway' => 'Takeaway',
                                    'delivery' => 'Delivery',
                                ])
                                ->default('dine-in')
                                ->placeholder('Select order type')
                                ->helperText(
                                    'How the customer will receive their order',
                                )
                                ->columnSpan(1),

                            Select::make('payment_method')
                                ->label('Payment Method')
                                ->required()
                                ->options(function ($get): array {
                                    $orderType = $get('order_type');

                                    if ($orderType === 'delivery') {
                                        return [
                                            'grab' => 'Grab',
                                            'food_panda' => 'Food Panda',
                                        ];
                                    }

                                    return [
                                        'cash' => 'Cash',
                                        'gcash' => 'GCash',
                                        'maya' => 'Maya',
                                    ];
                                })
                                ->default(function ($get): string {
                                    $orderType = $get('order_type');

                                    return $orderType === 'delivery' ? 'grab' : 'cash';
                                })
                                ->placeholder('Select payment method')
                                ->helperText(
                                    'Payment method used for this order',
                                )
                                ->reactive()
                                ->live()
                                ->columnSpan(1),

                            TextInput::make('table_number')
                                ->label('Table Number')
                                ->placeholder('e.g., A12')
                                ->helperText('Table number for dine-in orders')
                                // ->alphaNumeric()
                                ->columnSpan(1),

                            DateTimePicker::make('order_date')
                                ->label('Order Date')
                                ->default(now())
                                ->maxDate(now())
                                ->native(false)
                                ->displayFormat('M d, Y h:i A')
                                ->helperText(
                                    'Leave as current time or select a past date for backdated orders',
                                )
                                ->columnSpan(1),
                        ]),
                    ]),

                Section::make('Order Items')
                    ->description('Add products to this order')
                    ->icon('heroicon-o-queue-list')
                    ->schema([
                        Repeater::make('order_items')
                            ->label('')
                            ->schema([
                                Grid::make(12)->schema([
                                    Select::make('product_id')
                                        ->label('Product')
                                        ->options(
                                            Product::query()
                                                ->where('is_active', true)
                                                ->orderBy('name')
                                                ->pluck('name', 'id')
                                        )
                                        ->searchable()
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function (Get $get, Set $set, ?int $state): void {
                                            if (! $state) {
                                                $set('price', null);
                                                $set('product_variant_id', null);

                                                return;
                                            }

                                            $product = Product::query()->find($state);
                                            if ($product) {
                                                // Check if product has variants
                                                if ($product->variants()->exists()) {
                                                    // Get default variant or first variant
                                                    $defaultVariant = $product->variants()
                                                        ->where('is_active', true)
                                                        ->where('is_default', true)
                                                        ->first();

                                                    if ($defaultVariant) {
                                                        $set('product_variant_id', $defaultVariant->id);
                                                        $set('price', $defaultVariant->price);
                                                    } else {
                                                        $set('price', $product->price);
                                                    }
                                                } else {
                                                    $set('price', $product->price);
                                                    $set('product_variant_id', null);
                                                }
                                            }

                                            self::recalculateTotal($get, $set);
                                        })
                                        ->columnSpan(4),

                                    Select::make('product_variant_id')
                                        ->label('Variant')
                                        ->options(function (Get $get): array {
                                            $productId = $get('product_id');
                                            if (! $productId) {
                                                return [];
                                            }

                                            return ProductVariant::query()
                                                ->where('product_id', $productId)
                                                ->where('is_active', true)
                                                ->orderBy('sort_order')
                                                ->pluck('name', 'id')
                                                ->toArray();
                                        })
                                        ->live()
                                        ->afterStateUpdated(function (Get $get, Set $set, ?int $state): void {
                                            if ($state) {
                                                $variant = ProductVariant::query()->find($state);
                                                if ($variant) {
                                                    $set('price', $variant->price);
                                                }
                                            } else {
                                                // Fall back to product price
                                                $productId = $get('product_id');
                                                if ($productId) {
                                                    $product = Product::query()->find($productId);
                                                    if ($product) {
                                                        $set('price', $product->price);
                                                    }
                                                }
                                            }

                                            self::recalculateTotal($get, $set);
                                        })
                                        ->visible(function (Get $get): bool {
                                            $productId = $get('product_id');
                                            if (! $productId) {
                                                return false;
                                            }

                                            return ProductVariant::query()
                                                ->where('product_id', $productId)
                                                ->where('is_active', true)
                                                ->exists();
                                        })
                                        ->columnSpan(2),

                                    TextInput::make('quantity')
                                        ->label('Qty')
                                        ->numeric()
                                        ->default(1)
                                        ->minValue(1)
                                        ->required()
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (Get $get, Set $set): void {
                                            self::recalculateTotal($get, $set);
                                        })
                                        ->columnSpan(2),

                                    TextInput::make('price')
                                        ->label('Price')
                                        ->numeric()
                                        ->prefix(self::getCurrencyPrefix())
                                        ->suffix(self::getCurrencySuffix())
                                        ->required()
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (Get $get, Set $set): void {
                                            self::recalculateTotal($get, $set);
                                        })
                                        ->columnSpan(2),

                                    Placeholder::make('item_subtotal')
                                        ->label('Subtotal')
                                        ->content(function (Get $get): string {
                                            $quantity = (int) ($get('quantity') ?? 1);
                                            $price = (float) ($get('price') ?? 0);

                                            return self::formatCurrency($quantity * $price);
                                        })
                                        ->columnSpan(2),
                                ]),

                                Textarea::make('notes')
                                    ->label('Item Notes')
                                    ->placeholder('Special instructions for this item...')
                                    ->rows(1)
                                    ->columnSpanFull(),
                            ])
                            ->columns(1)
                            ->defaultItems(0)
                            ->addActionLabel('Add Product')
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(function (array $state): ?string {
                                $productId = $state['product_id'] ?? null;
                                $quantity = $state['quantity'] ?? 1;

                                if (! $productId) {
                                    return null;
                                }

                                $product = Product::query()->find($productId);
                                if (! $product) {
                                    return null;
                                }

                                $variantId = $state['product_variant_id'] ?? null;
                                $variantName = '';
                                if ($variantId) {
                                    $variant = ProductVariant::query()->find($variantId);
                                    if ($variant) {
                                        $variantName = " ({$variant->name})";
                                    }
                                }

                                return "{$product->name}{$variantName} x {$quantity}";
                            }),

                        Grid::make(2)->schema([
                            Placeholder::make('items_count')
                                ->label('Items Count')
                                ->content(function (Get $get): string {
                                    $items = $get('order_items') ?? [];
                                    $totalItems = 0;

                                    foreach ($items as $item) {
                                        $totalItems += (int) ($item['quantity'] ?? 0);
                                    }

                                    return (string) $totalItems;
                                }),

                            Placeholder::make('calculated_total')
                                ->label('Calculated Total')
                                ->content(function (Get $get): string {
                                    $items = $get('order_items') ?? [];
                                    $total = 0.0;

                                    foreach ($items as $item) {
                                        $quantity = (int) ($item['quantity'] ?? 0);
                                        $price = (float) ($item['price'] ?? 0);
                                        $total += $quantity * $price;
                                    }

                                    return self::formatCurrency($total);
                                }),
                        ]),
                    ])
                    ->collapsed(fn ($record): bool => $record !== null),

                Section::make('Order Status & Amount')
                    ->description('Manage order status and financial details')
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('status')
                                ->label('Order Status')
                                ->required()
                                ->options([
                                    'pending' => 'Pending',
                                    'confirmed' => 'Confirmed',
                                    'preparing' => 'Preparing',
                                    'ready' => 'Ready',
                                    'served' => 'Served',
                                    'completed' => 'Completed',
                                    'cancelled' => 'Cancelled',
                                ])
                                ->default('pending')
                                ->placeholder('Select order status')
                                ->helperText('Current status of this order')
                                ->columnSpan(1),

                            TextInput::make('total')
                                ->label('Total Amount')
                                ->required()
                                ->numeric()
                                ->prefix(self::getCurrencyPrefix())
                                ->suffix(self::getCurrencySuffix())
                                ->step(0.01)
                                ->placeholder('0.00')
                                ->helperText('Total amount for this order')
                                ->columnSpan(1),
                        ]),
                    ]),

                Section::make('Additional Information')
                    ->description('Extra details and notes')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Order Notes')
                            ->placeholder(
                                'e.g., Extra napkins needed, customer has allergies, etc.',
                            )
                            ->helperText(
                                'Any special instructions or notes about this order',
                            )
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Order Summary')
                    ->description('Order overview and statistics')
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        Grid::make(4)->schema([
                            Placeholder::make('order_summary')
                                ->label('Order Summary')
                                ->content(function ($record): string {
                                    if (! $record) {
                                        return 'New Order';
                                    }

                                    $statusColors = [
                                        'pending' => '🟡',
                                        'confirmed' => '🔵',
                                        'preparing' => '🟠',
                                        'ready' => '🟢',
                                        'served' => '✅',
                                        'completed' => '✅',
                                        'cancelled' => '🔴',
                                    ];

                                    $color =
                                        $statusColors[$record->status] ?? '⚪';

                                    return $color.
                                        ' '.
                                        ucfirst($record->status ?? 'Unknown');
                                })
                                ->columnSpan(1),

                            Placeholder::make('order_type_display')
                                ->label('Order Type')
                                ->content(function ($record): string {
                                    if (! $record) {
                                        return 'Not Set';
                                    }

                                    $types = [
                                        'dine-in' => '🍽️ Dine In',
                                        'takeaway' => '🥤 Takeaway',
                                        'delivery' => '🚚 Delivery',
                                    ];

                                    return $types[$record->order_type] ??
                                        '❓ Unknown';
                                })
                                ->columnSpan(1),

                            Placeholder::make('payment_display')
                                ->label('Payment')
                                ->content(function ($record): string {
                                    if (! $record) {
                                        return 'Not Set';
                                    }

                                    $methods = [
                                        'cash' => '💵 Cash',
                                        'gcash' => '📱 GCash',
                                        'maya' => '📱 Maya',
                                        'grab' => '🚗 Grab',
                                        'food_panda' => '🥡 Food Panda',
                                    ];

                                    return $methods[$record->payment_method] ??
                                        '❓ Unknown';
                                })
                                ->columnSpan(1),

                            Placeholder::make('total_formatted')
                                ->label('Total')
                                ->content(function ($record): string {
                                    if (! $record) {
                                        return self::formatCurrency(0);
                                    }

                                    return self::formatCurrency(
                                        $record->total,
                                    );
                                })
                                ->columnSpan(1),
                        ]),
                    ])
                    ->visible(fn ($record): bool => $record !== null),
            ])
            ->columns(1);
    }

    /**
     * Recalculate the total based on order items.
     */
    private static function recalculateTotal(Get $get, Set $set): void
    {
        // Navigate up from the repeater item to get order_items
        $items = $get('../../order_items') ?? [];
        $total = 0.0;

        foreach ($items as $item) {
            $quantity = (int) ($item['quantity'] ?? 0);
            $price = (float) ($item['price'] ?? 0);
            $total += $quantity * $price;
        }

        // Set the total field (navigate up to root level)
        $set('../../total', $total);
    }
}
