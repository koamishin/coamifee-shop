<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Schemas;

use App\Filament\Concerns\CurrencyAware;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
// use Filament\\Components\RepeatableEntry;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;

final class OrderInfolist
{
    use CurrencyAware;

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Flex::make([
                Grid::make(1)
                    ->schema([
                        Section::make('Order Information')
                            ->icon('heroicon-o-shopping-cart')
                            ->schema([
                                TextEntry::make('id')
                                    ->label('Order Number')
                                    ->prefix('#')
                                    ->weight(FontWeight::Bold)
                                    ->size(TextSize::Large)
                                    ->color('primary'),

                                TextEntry::make('status')
                                    ->label('Order Status')
                                    ->badge()
                                    ->icon(fn ($state): string => match ($state) {
                                        'pending' => 'heroicon-o-clock',
                                        'confirmed' => 'heroicon-o-check-circle',
                                        'preparing' => 'heroicon-o-arrow-path',
                                        'ready' => 'heroicon-o-bell-alert',
                                        'served' => 'heroicon-o-check-badge',
                                        'completed' => 'heroicon-o-check',
                                        'cancelled' => 'heroicon-o-x-circle',
                                        default => 'heroicon-o-question-mark-circle',
                                    })
                                    ->color(fn ($state): string => match ($state) {
                                        'pending' => 'warning',
                                        'confirmed' => 'info',
                                        'preparing' => 'primary',
                                        'ready' => 'success',
                                        'served' => 'success',
                                        'completed' => 'success',
                                        'cancelled' => 'danger',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn ($state): string => ucfirst((string) $state)),

                                TextEntry::make('order_type')
                                    ->label('Order Type')
                                    ->badge()
                                    ->icon(fn ($state): string => match ($state) {
                                        'dine_in', 'dine-in' => 'heroicon-o-building-storefront',
                                        'takeaway' => 'heroicon-o-shopping-bag',
                                        'delivery' => 'heroicon-o-truck',
                                        default => 'heroicon-o-question-mark-circle',
                                    })
                                    ->color(fn ($state): string => match ($state) {
                                        'dine_in', 'dine-in' => 'success',
                                        'takeaway' => 'info',
                                        'delivery' => 'warning',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn ($state): string => match ($state) {
                                        'dine_in', 'dine-in' => 'Dine In',
                                        'takeaway' => 'Takeaway',
                                        'delivery' => 'Delivery',
                                        default => ucfirst(str_replace('_', ' ', (string) $state)),
                                    }),

                                TextEntry::make('table_number')
                                    ->label('Table Number')
                                    ->badge()
                                    ->icon('heroicon-o-building-office-2')
                                    ->color('primary')
                                    ->placeholder('N/A')
                                    ->formatStateUsing(fn ($state): string => $state ? str_replace('_', ' ', ucfirst($state)) : 'N/A')
                                    ->visible(fn ($record) => $record->order_type === 'dine_in' || $record->order_type === 'dine-in'),

                                TextEntry::make('created_at')
                                    ->label('Order Placed')
                                    ->dateTime('l, F j, Y \a\t g:i A')
                                    ->icon('heroicon-o-calendar'),

                                TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->since()
                                    ->icon('heroicon-o-clock'),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),

                        Section::make('Customer Information')
                            ->icon('heroicon-o-user-circle')
                            ->schema([
                                TextEntry::make('customer_name')
                                    ->label('Customer Name')
                                    ->placeholder('Not provided')
                                    ->icon('heroicon-o-user')
                                    ->weight(FontWeight::Bold),

                                TextEntry::make('customer.name')
                                    ->label('Registered Account')
                                    ->placeholder('Guest Customer')
                                    ->badge()
                                    ->icon(fn ($record) => $record->customer_id ? 'heroicon-o-check-badge' : 'heroicon-o-user')
                                    ->color(fn ($record): string => $record->customer_id ? 'success' : 'gray')
                                    ->formatStateUsing(fn ($state) => $state ?: 'Guest'),

                                TextEntry::make('customer.email')
                                    ->label('Email')
                                    ->placeholder('Not available')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable()
                                    ->visible(fn ($record) => $record->customer_id),

                                TextEntry::make('customer.phone')
                                    ->label('Phone')
                                    ->placeholder('Not available')
                                    ->icon('heroicon-o-phone')
                                    ->copyable()
                                    ->visible(fn ($record) => $record->customer_id),
                            ])
                            ->columns(2),
                    ]),

            ])->from('lg'),

            Section::make('Order Summary')
                ->icon('heroicon-o-calculator')
                ->schema([
                    TextEntry::make('subtotal')
                        ->label('Subtotal')
                        ->money(self::getMoneyConfig())
                        ->size(TextSize::Medium),

                    TextEntry::make('discount_type')
                        ->label('Discount Type')
                        ->badge()
                        ->color('info')
                        ->formatStateUsing(fn ($state): string => $state ? ucfirst((string) $state) : 'None')
                        ->visible(fn ($record) => $record->discount_amount > 0),

                    TextEntry::make('discount_value')
                        ->label('Discount Value')
                        ->formatStateUsing(fn ($state, $record): string => $record->discount_type === 'percentage' ? "{$state}%" : self::getMoneyConfig()['currency'].' '.$state)
                        ->visible(fn ($record) => $record->discount_amount > 0),

                    TextEntry::make('discount_amount')
                        ->label('Discount Amount')
                        ->money(self::getMoneyConfig())
                        ->color('danger')
                        ->icon('heroicon-o-tag')
                        ->visible(fn ($record) => $record->discount_amount > 0),

                    TextEntry::make('add_ons_total')
                        ->label('Add-ons Total')
                        ->money(self::getMoneyConfig())
                        ->color('success')
                        ->icon('heroicon-o-plus-circle')
                        ->visible(fn ($record) => $record->add_ons_total > 0),

                    TextEntry::make('total')
                        ->label('Total Amount')
                        ->money(self::getMoneyConfig())
                        ->weight(FontWeight::Bold)
                        ->size(TextSize::Large)
                        ->color('success')
                        ->icon('heroicon-o-currency-dollar'),
                    TextEntry::make('payment_method')
                        ->label('Payment Method')
                        ->badge()
                        ->icon(fn ($state): string => app(\App\Services\GeneralSettingsService::class)->getPaymentMethodIcon((string) $state))
                        ->color(fn ($state): string => app(\App\Services\GeneralSettingsService::class)->getPaymentMethodColor((string) $state))
                        ->formatStateUsing(fn ($state): string => app(\App\Services\GeneralSettingsService::class)->getPaymentMethodDisplayName((string) $state)),
                ]),

            Section::make('Order Items')
                ->icon('heroicon-o-shopping-bag')
                ->description('Detailed list of items in this order')
                ->schema([
                    RepeatableEntry::make('items')
                        ->label('')
                        ->schema([
                            Grid::make(6)
                                ->schema([
                                    TextEntry::make('product.name')
                                        ->label('Product')
                                        ->weight(FontWeight::Bold)
                                        ->size(TextSize::Medium)
                                        ->icon('heroicon-o-cube'),

                                    TextEntry::make('variant_name')
                                        ->label('Variant')
                                        ->badge()
                                        ->color('info')
                                        ->placeholder('-'),

                                    TextEntry::make('quantity')
                                        ->label('Qty')
                                        ->badge()
                                        ->color('primary')
                                        ->suffix('x'),

                                    TextEntry::make('price')
                                        ->label('Unit Price')
                                        ->money(self::getMoneyConfig()),

                                    TextEntry::make('subtotal')
                                        ->label('Subtotal')
                                        ->money(self::getMoneyConfig())
                                        ->weight(FontWeight::Bold)
                                        ->color('success'),

                                    TextEntry::make('is_served')
                                        ->label('Served')
                                        ->badge()
                                        ->icon(fn ($state): string => $state ? 'heroicon-o-check-circle' : 'heroicon-o-clock')
                                        ->color(fn ($state): string => $state ? 'success' : 'warning')
                                        ->formatStateUsing(fn ($state): string => $state ? 'Served' : 'Pending'),
                                ]),

                            TextEntry::make('notes')
                                ->label('Special Instructions')
                                ->placeholder('No special instructions')
                                ->icon('heroicon-o-chat-bubble-bottom-center-text')
                                ->color('gray')
                                ->columnSpanFull(),
                        ])
                        ->contained(false),
                ])
                ->columnSpanFull(),

            Section::make('Additional Information')
                ->icon('heroicon-o-information-circle')
                ->schema([
                    TextEntry::make('notes')
                        ->label('Order Notes')
                        ->placeholder('No additional notes')
                        ->columnSpanFull()
                        ->icon('heroicon-o-pencil-square'),

                    TextEntry::make('add_ons')
                        ->label('Add-ons')
                        ->placeholder('No add-ons')
                        ->columnSpanFull()
                        ->formatStateUsing(function ($state) {
                            if (! $state || ! is_array($state)) {
                                return 'No add-ons';
                            }

                            return collect($state)->map(function ($addon) {
                                $name = is_array($addon) ? ($addon['name'] ?? 'Unknown') : $addon;
                                $price = is_array($addon) && isset($addon['price']) ? ' - PHP '.number_format($addon['price'], 2) : '';

                                return '• '.$name.$price;
                            })->join("\n");
                        })
                        ->visible(fn ($record) => $record->add_ons && is_array($record->add_ons) && count($record->add_ons) > 0),
                ])
                ->collapsible()
                ->collapsed(),
        ]);
    }
}
