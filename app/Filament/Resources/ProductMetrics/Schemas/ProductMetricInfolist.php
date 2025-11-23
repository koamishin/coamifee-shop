<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductMetrics\Schemas;

use App\Filament\Concerns\CurrencyAware;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;

final class ProductMetricInfolist
{
    use CurrencyAware;

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Flex::make([
                Grid::make(1)
                    ->schema([
                        Section::make('Product Information')
                            ->icon('heroicon-o-cube')
                            ->schema([
                                TextEntry::make('product.name')
                                    ->label('Product Name')
                                    ->weight(FontWeight::Bold)
                                    ->size(TextSize::Large)
                                    ->color('primary')
                                    ->icon('heroicon-o-shopping-bag'),

                                TextEntry::make('product.category.name')
                                    ->label('Category')
                                    ->badge()
                                    ->color('info')
                                    ->icon('heroicon-o-tag')
                                    ->placeholder('No category'),

                                TextEntry::make('product.price')
                                    ->label('Product Price')
                                    ->money(self::getMoneyConfig())
                                    ->icon('heroicon-o-currency-dollar'),
                            ])
                            ->columns(3),

                        Section::make('Metric Details')
                            ->icon('heroicon-o-calendar')
                            ->schema([
                                TextEntry::make('metric_date')
                                    ->label('Metric Date')
                                    ->date('l, F j, Y')
                                    ->icon('heroicon-o-calendar-days')
                                    ->weight(FontWeight::Medium),

                                TextEntry::make('period_type')
                                    ->label('Period Type')
                                    ->badge()
                                    ->icon(fn ($state): string => match ($state) {
                                        'daily' => 'heroicon-o-calendar-days',
                                        'weekly' => 'heroicon-o-calendar',
                                        'monthly' => 'heroicon-o-chart-bar',
                                        default => 'heroicon-o-question-mark-circle',
                                    })
                                    ->color(fn ($state): string => match ($state) {
                                        'daily' => 'info',
                                        'weekly' => 'warning',
                                        'monthly' => 'success',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn ($state): string => match ($state) {
                                        'daily' => 'Daily',
                                        'weekly' => 'Weekly',
                                        'monthly' => 'Monthly',
                                        default => ucfirst((string) $state),
                                    }),

                                TextEntry::make('created_at')
                                    ->label('Recorded At')
                                    ->dateTime('M j, Y g:i A')
                                    ->icon('heroicon-o-clock')
                                    ->placeholder('-'),

                                TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->since()
                                    ->icon('heroicon-o-arrow-path')
                                    ->placeholder('-'),
                            ])
                            ->columns(2),
                    ]),

                Section::make('Performance Metrics')
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        TextEntry::make('orders_count')
                            ->label('Total Orders')
                            ->numeric()
                            ->badge()
                            ->color('primary')
                            ->icon('heroicon-o-shopping-cart')
                            ->formatStateUsing(fn ($state): string => number_format((int) $state).' orders'),

                        TextEntry::make('total_revenue')
                            ->label('Total Sales')
                            ->money(self::getMoneyConfig())
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large)
                            ->color('success')
                            ->icon('heroicon-o-currency-dollar'),

                        TextEntry::make('average_order_value')
                            ->label('Average Order Value')
                            ->money(self::getMoneyConfig())
                            ->weight(FontWeight::Medium)
                            ->color('info')
                            ->icon('heroicon-o-calculator')
                            ->formatStateUsing(function ($record): string {
                                $orders = (int) $record->orders_count;
                                $sales = (float) $record->total_revenue;

                                if ($orders === 0) {
                                    return self::getMoneyConfig()['currency'].' 0.0';
                                }

                                $aov = $sales / $orders;

                                return self::getMoneyConfig()['currency'].' '.number_format($aov, 2);
                            }),

                        TextEntry::make('revenue_per_day')
                            ->label('Sales Per Day')
                            ->money(self::getMoneyConfig())
                            ->color('warning')
                            ->icon('heroicon-o-banknotes')
                            ->formatStateUsing(function ($record): string {
                                $sales = (float) $record->total_revenue;
                                $period = $record->period_type ?? 'daily';

                                $days = match ($period) {
                                    'daily' => 1,
                                    'weekly' => 7,
                                    'monthly' => 30,
                                    default => 1,
                                };

                                $salesPerDay = $sales / $days;

                                return self::getMoneyConfig()['currency'].' '.number_format($salesPerDay, 2);
                            }),

                        TextEntry::make('performance')
                            ->label('Performance Rating')
                            ->badge()
                            ->size(TextSize::Large)
                            ->formatStateUsing(function ($record): string {
                                $orders = (int) $record->orders_count;
                                $sales = (float) $record->total_revenue;

                                if ($orders === 0 && $sales === 0.0) {
                                    return '⚪ No Activity';
                                }

                                $aov = $orders > 0 ? $sales / $orders : 0;

                                if ($aov >= 50 && $orders >= 10) {
                                    return '🟢 Excellent';
                                }

                                if ($aov >= 25 && $orders >= 5) {
                                    return '🟠 Good';
                                }

                                return '🔴 Needs Attention';
                            })
                            ->color(fn ($state): string => match (true) {
                                str_contains((string) $state, 'Excellent') => 'success',
                                str_contains((string) $state, 'Good') => 'warning',
                                str_contains((string) $state, 'No Activity') => 'gray',
                                default => 'danger',
                            }),
                    ])
                    ->columns(2)
                    ->grow(false),
            ])->from('md'),
        ]);
    }
}
