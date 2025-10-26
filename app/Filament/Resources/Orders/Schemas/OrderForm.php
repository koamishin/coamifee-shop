<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class OrderForm
{
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

                        Grid::make(3)->schema([
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
                                ->options([
                                    'cash' => 'Cash',
                                    'card' => 'Card',
                                    'digital' => 'Digital Wallet',
                                    'bank_transfer' => 'Bank Transfer',
                                ])
                                ->default('cash')
                                ->placeholder('Select payment method')
                                ->helperText(
                                    'Payment method used for this order',
                                )
                                ->columnSpan(1),

                            TextInput::make('table_number')
                                ->label('Table Number')
                                ->placeholder('e.g., A12')
                                ->helperText('Table number for dine-in orders')
                                // ->alphaNumeric()
                                ->columnSpan(1),
                        ]),
                    ]),

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
                                ->prefix('$')
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
                                ->content(function ($record) {
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
                                ->content(function ($record) {
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
                                ->content(function ($record) {
                                    if (! $record) {
                                        return 'Not Set';
                                    }

                                    $methods = [
                                        'cash' => '💵 Cash',
                                        'card' => '💳 Card',
                                        'digital' => '📱 Digital',
                                        'bank_transfer' => '🏦 Bank Transfer',
                                    ];

                                    return $methods[$record->payment_method] ??
                                        '❓ Unknown';
                                })
                                ->columnSpan(1),

                            Placeholder::make('total_formatted')
                                ->label('Total')
                                ->content(function ($record) {
                                    if (! $record) {
                                        return '$0.00';
                                    }

                                    return '$'.
                                        number_format($record->total, 2);
                                })
                                ->columnSpan(1),
                        ]),
                    ])
                    ->visible(fn ($record) => $record !== null),
            ])
            ->columns(1);
    }
}
