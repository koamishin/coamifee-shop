<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductMetrics\Schemas;

use App\Filament\Concerns\CurrencyAware;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

final class ProductMetricForm
{
    use CurrencyAware;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Product Performance Metrics')
                    ->description(
                        'Track and analyze product performance over time',
                    )
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('product_id')
                                ->label('Product')
                                ->relationship('product', 'name')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->placeholder('Select a product')
                                ->helperText(
                                    'Choose the product to track metrics for',
                                )
                                ->reactive()
                                ->afterStateUpdated(
                                    fn ($state, callable $set) => $set(
                                        'orders_count',
                                        0,
                                    ),
                                )
                                ->columnSpan(1),

                            Select::make('period_type')
                                ->label('Period Type')
                                ->options([
                                    'daily' => '📅 Daily',
                                    'weekly' => '📊 Weekly',
                                    'monthly' => '📈 Monthly',
                                ])
                                ->required()
                                ->placeholder('Select time period')
                                ->helperText(
                                    'Select the time period for these metrics',
                                )
                                ->default('daily')
                                ->columnSpan(1),
                        ]),

                        DatePicker::make('metric_date')
                            ->label('Metric Date')
                            ->required()
                            ->placeholder('Select date')
                            ->helperText(
                                'Date when these metrics were recorded',
                            )
                            ->closeOnDateSelection()
                            ->weekStartsOnSunday()
                            ->columnSpanFull(),
                    ]),

                Section::make('Sales Performance Data')
                    ->description('Enter sales and revenue information')
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('orders_count')
                                ->label('Total Orders')
                                ->required()
                                ->numeric()
                                ->minValue(0)
                                ->default(0)
                                ->prefixIcon('heroicon-o-shopping-cart')
                                ->placeholder('e.g., 25')
                                ->helperText(
                                    'Number of orders placed for this product',
                                )
                                ->reactive()
                                ->afterStateUpdated(
                                    fn (
                                        $state,
                                        callable $set,
                                    ) => self::calculateAverageOrderValue(
                                        $state,
                                        $get('total_revenue'),
                                    ),
                                )
                                ->columnSpan(1),

                            TextInput::make('total_revenue')
                                ->label('Total Revenue')
                                ->required()
                                ->numeric()
                                ->minValue(0)
                                ->step(0.01)
                                ->default(0)
                                ->prefix(self::getCurrencyPrefix())
                                ->suffix(self::getCurrencySuffix())
                                ->prefixIcon('heroicon-o-banknotes')
                                ->placeholder('e.g., 150.00')
                                ->helperText(
                                    'Total revenue generated from these orders',
                                )
                                ->reactive()
                                ->afterStateUpdated(
                                    fn (
                                        $state,
                                        callable $set,
                                    ) => self::calculateAverageOrderValue(
                                        $get('orders_count'),
                                        $state,
                                    ),
                                )
                                ->columnSpan(1),
                        ]),

                        Section::make('Performance Analytics')
                            ->description('Automatically calculated metrics')
                            ->icon('heroicon-o-calculator')
                            ->collapsible()
                            ->schema([
                                Grid::make(3)->schema([
                                    Placeholder::make('average_order_value')
                                        ->label('Average Order Value')
                                        ->content(function ($get): HtmlString {
                                            $orders =
                                                (float) ($get('orders_count') ??
                                                    0);
                                            $revenue =
                                                (float) ($get(
                                                    'total_revenue',
                                                ) ?? 0);

                                            if ($orders > 0) {
                                                $average = $revenue / $orders;
                                                $color =
                                                    $average >= 50
                                                        ? '#10b981'
                                                        : ($average >= 25
                                                            ? '#f59e0b'
                                                            : '#ef4444');

                                                return new HtmlString(
                                                    '<span style="font-weight: bold; font-size: 1.1em; color: '.
                                                        $color.
                                                        ';">'.
                                                        self::formatCurrency(
                                                            $average,
                                                        ).
                                                        '</span>',
                                                );
                                            }

                                            return new HtmlString(
                                                '<span style="color: #6b7280;">'.
                                                    self::formatCurrency(
                                                        0.0,
                                                    ).
                                                    '</span>',
                                            );
                                        })
                                        ->helperText('Revenue ÷ Orders')
                                        ->columnSpan(1),

                                    Placeholder::make('revenue_per_day')
                                        ->label('Revenue per Day')
                                        ->content(function ($get): HtmlString {
                                            $revenue =
                                                (float) ($get(
                                                    'total_revenue',
                                                ) ?? 0);
                                            $period =
                                                $get('period_type') ?? 'daily';

                                            $days = match ($period) {
                                                'daily' => 1,
                                                'weekly' => 7,
                                                'monthly' => 30,
                                                default => 1,
                                            };

                                            $revenuePerDay = $revenue / $days;

                                            return new HtmlString(
                                                '<span style="font-weight: 600; color: #3b82f6;">'.
                                                    self::formatCurrency(
                                                        $revenuePerDay,
                                                    ).
                                                    '</span>',
                                            );
                                        })
                                        ->helperText('Revenue ÷ Days in period')
                                        ->columnSpan(1),

                                    Placeholder::make('performance_indicator')
                                        ->label('Performance')
                                        ->content(function ($get): HtmlString {
                                            $orders =
                                                (float) ($get('orders_count') ??
                                                    0);
                                            $revenue =
                                                (float) ($get(
                                                    'total_revenue',
                                                ) ?? 0);

                                            if (
                                                $orders === 0 &&
                                                $revenue === 0
                                            ) {
                                                return new HtmlString(
                                                    '<span style="color: #6b7280;">⚪ No Activity</span>',
                                                );
                                            }

                                            $average =
                                                $orders > 0
                                                    ? $revenue / $orders
                                                    : 0;

                                            if (
                                                $average >= 50 &&
                                                $orders >= 10
                                            ) {
                                                return new HtmlString(
                                                    '<span style="color: #10b981; font-weight: bold;">🟢 Excellent</span>',
                                                );
                                            }

                                            if (
                                                $average >= 25 &&
                                                $orders >= 5
                                            ) {
                                                return new HtmlString(
                                                    '<span style="color: #f59e0b; font-weight: bold;">🟠 Good</span>',
                                                );
                                            }

                                            return new HtmlString(
                                                '<span style="color: #ef4444; font-weight: bold;">🔴 Needs Attention</span>',
                                            );
                                        })
                                        ->helperText(
                                            'Overall performance rating',
                                        )
                                        ->columnSpan(1),
                                ]),
                            ]),
                    ]),
            ])
            ->columns(1);
    }

    private static function calculateAverageOrderValue(
        $orders,
        $revenue,
    ): void {
        $orders = (float) ($orders ?? 0);
        $revenue = (float) ($revenue ?? 0);

        if ($orders > 0) {
            $average = $revenue / $orders;
            // Update the calculated placeholder if needed
        }
    }
}
