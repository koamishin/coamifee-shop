<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductMetrics\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

final class ProductMetricForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Product & Period')
                    ->description('Select product and time period for metrics')
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('product_id')
                                    ->label('Product')
                                    ->relationship('product', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Select a product')
                                    ->helperText('Choose product to track metrics for'),
                                Select::make('period_type')
                                    ->label('Period Type')
                                    ->options([
                                        'daily' => 'Daily',
                                        'weekly' => 'Weekly',
                                        'monthly' => 'Monthly',
                                    ])
                                    ->required()
                                    ->helperText('Select time period for these metrics'),
                            ]),
                        DatePicker::make('metric_date')
                            ->label('Metric Date')
                            ->required()
                            ->placeholder('Select date')
                            ->helperText('Date when these metrics were recorded')
                            ->columnSpanFull(),
                    ]),

                Section::make('Sales Performance')
                    ->description('Enter sales and revenue data')
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('orders_count')
                                    ->label('Total Orders')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->prefixIcon('heroicon-o-shopping-cart')
                                    ->placeholder('e.g., 25')
                                    ->helperText('Number of orders for this period')
                                    ->columnSpan(1),
                                TextInput::make('total_revenue')
                                    ->label('Total Revenue')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('$')
                                    ->prefixIcon('heroicon-o-cash')
                                    ->placeholder('e.g., 150.00')
                                    ->helperText('Total revenue generated from orders')
                                    ->columnSpan(1),
                            ]),
                        Placeholder::make('average_order_value')
                            ->label('Average Order Value')
                            ->content(function ($get) {
                                $orders = (float) ($get('orders_count') ?? 0);
                                $revenue = (float) ($get('total_revenue') ?? 0);

                                if ($orders > 0) {
                                    $average = $revenue / $orders;

                                    return new HtmlString('<span style="font-weight: bold; color: #10b981;">$'.number_format($average, 2).'</span>');
                                }

                                return new HtmlString('<span style="color: #6b7280;">$0.00</span>');
                            })
                            ->helperText('Calculated: Total Revenue ÷ Total Orders')
                            ->columnSpanFull(),
                    ]),
            ])
            ->columns(1);
    }
}
