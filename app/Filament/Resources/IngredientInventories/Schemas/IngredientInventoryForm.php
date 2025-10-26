<?php

declare(strict_types=1);

namespace App\Filament\Resources\IngredientInventories\Schemas;

use App\Filament\Concerns\CurrencyAware;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

final class IngredientInventoryForm
{
    use CurrencyAware;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Inventory Configuration')
                    ->description(
                        'Set up stock tracking and levels for this ingredient',
                    )
                    ->icon('heroicon-o-archive-box')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('ingredient_id')
                                ->label('Ingredient')
                                ->relationship('ingredient', 'name')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->placeholder('Select an ingredient')
                                ->helperText(
                                    'Choose ingredient to manage inventory for',
                                )
                                ->reactive()
                                ->afterStateUpdated(
                                    fn (
                                        $state,
                                        callable $set,
                                    ) => self::populateIngredientDefaults(
                                        $state,
                                        $set,
                                    ),
                                )
                                ->columnSpan(2),

                            TextInput::make('current_stock')
                                ->label('Current Stock')
                                ->required()
                                ->numeric()
                                ->step(0.001)
                                ->minValue(0)
                                ->placeholder('e.g., 5000')
                                ->helperText('Current available quantity')
                                ->prefixIcon('heroicon-o-cube')
                                ->suffix(fn ($get) => self::getUnitSuffix($get))
                                ->reactive()
                                ->afterStateUpdated(
                                    fn (
                                        $state,
                                        callable $set,
                                    ) => self::updateStockStatus(
                                        $state,
                                        $get,
                                        $set,
                                    ),
                                )
                                ->columnSpan(1),

                            TextInput::make('reorder_level')
                                ->label('Reorder Level')
                                ->numeric()
                                ->step(0.001)
                                ->minValue(0)
                                ->placeholder('e.g., 500')
                                ->helperText(
                                    'Automatically reorder when stock falls below this level',
                                )
                                ->prefixIcon('heroicon-o-bell-alert')
                                ->suffix(fn ($get) => self::getUnitSuffix($get))
                                ->columnSpan(1),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('min_stock_level')
                                ->label('Minimum Stock')
                                ->numeric()
                                ->step(0.001)
                                ->minValue(0)
                                ->placeholder('e.g., 100')
                                ->helperText('Minimum acceptable stock level')
                                ->prefixIcon('heroicon-o-arrow-down')
                                ->suffix(fn ($get) => self::getUnitSuffix($get))
                                ->columnSpan(1),

                            TextInput::make('max_stock_level')
                                ->label('Maximum Stock')
                                ->numeric()
                                ->step(0.001)
                                ->minValue(0)
                                ->placeholder('e.g., 10000')
                                ->helperText('Maximum stock capacity')
                                ->prefixIcon('heroicon-o-arrow-up')
                                ->suffix(fn ($get) => self::getUnitSuffix($get))
                                ->columnSpan(1),
                        ]),

                        TextInput::make('location')
                            ->label('Storage Location')
                            ->placeholder(
                                'e.g., Main Storage, Fridge A, Freezer B1',
                            )
                            ->helperText(
                                'Physical location where this ingredient is stored',
                            )
                            ->prefixIcon('heroicon-o-map-pin')
                            ->columnSpanFull(),

                        TextInput::make('supplier_info')
                            ->label('Supplier Information')
                            ->placeholder(
                                'e.g., Local Coffee Roasters - Contact: 555-0123',
                            )
                            ->helperText(
                                'Supplier details and contact information',
                            )
                            ->prefixIcon('heroicon-o-building-office')
                            ->columnSpanFull(),
                    ]),

                Section::make('Stock Status & Analysis')
                    ->description(
                        'Current inventory status and recommendations',
                    )
                    ->icon('heroicon-o-chart-pie')
                    ->collapsible()
                    ->schema([
                        Grid::make(4)->schema([
                            Placeholder::make('stock_status')
                                ->label('Stock Status')
                                ->content(
                                    fn (
                                        $get,
                                        $record,
                                    ) => self::getStockStatusDisplay(
                                        $get,
                                        $record,
                                    ),
                                )
                                ->columnSpan(1),

                            Placeholder::make('stock_percentage')
                                ->label('Stock Level')
                                ->content(
                                    fn ($get) => self::getStockPercentage($get),
                                )
                                ->columnSpan(1),

                            Placeholder::make('days_until_reorder')
                                ->label('Days Until Reorder')
                                ->content(
                                    fn ($get) => self::getDaysUntilReorder($get),
                                )
                                ->columnSpan(1),

                            Placeholder::make('recommended_order')
                                ->label('Recommended Order')
                                ->content(
                                    fn ($get) => self::getRecommendedOrder($get),
                                )
                                ->columnSpan(1),
                        ]),
                    ]),

                Section::make('Cost & Value Analysis')
                    ->description('Financial analysis of current inventory')
                    ->icon('heroicon-o-currency-dollar')
                    ->collapsible()
                    ->schema([
                        Grid::make(3)->schema([
                            Placeholder::make('total_value')
                                ->label('Total Value')
                                ->content(
                                    fn ($get) => self::calculateTotalValue($get),
                                )
                                ->columnSpan(1),

                            Placeholder::make('cost_per_unit')
                                ->label('Cost per Unit')
                                ->content(
                                    fn ($get) => self::getCostPerUnit($get),
                                )
                                ->columnSpan(1),

                            Placeholder::make('turnover_rate')
                                ->label('Turnover Rate')
                                ->content(
                                    fn ($get) => self::getTurnoverRate($get),
                                )
                                ->columnSpan(1),
                        ]),
                    ]),
            ])
            ->columns(1);
    }

    private static function populateIngredientDefaults(
        $ingredientId,
        callable $set,
    ): void {
        if (! $ingredientId) {
            return;
        }

        $ingredient = \App\Models\Ingredient::find($ingredientId);
        if (! $ingredient) {
            return;
        }

        // Set default values based on ingredient type
        $set('min_stock_level', 100);
        $set('max_stock_level', 10000);
        $set('reorder_level', 500);

        // If ingredient has supplier info, populate it
        if ($ingredient->supplier) {
            $set('supplier_info', $ingredient->supplier);
        }
    }

    private static function getUnitSuffix(callable $get): ?string
    {
        $ingredientId = $get('ingredient_id');

        if (! $ingredientId) {
            return null;
        }

        $ingredient = \App\Models\Ingredient::find($ingredientId);

        return $ingredient?->unit_type;
    }

    private static function updateStockStatus(
        $stock,
        callable $get,
        callable $set,
    ): void {
        $current = (float) ($stock ?? 0);
        $min = (float) ($get('min_stock_level') ?? 0);
        $max = (float) ($get('max_stock_level') ?? PHP_FLOAT_MAX);

        $status = match (true) {
            $current <= $min => 'Low Stock',
            $current >= $max => 'Overstocked',
            default => 'Normal',
        };

        // Could trigger notifications or other actions here
    }

    private static function getStockStatusDisplay(
        callable $get,
        $record,
    ): HtmlString|string {
        $current = (float) ($get('current_stock') ?? 0);
        $min = (float) ($get('min_stock_level') ?? 0);
        $max = (float) ($get('max_stock_level') ?? PHP_FLOAT_MAX);

        if (! $record && $current === 0) {
            return new HtmlString(
                '<span style="color: #6b7280;">⚪ New Inventory</span>',
            );
        }

        if ($current <= $min) {
            return new HtmlString(
                '<span style="color: #dc2626; font-weight: bold;">🔴 Low Stock</span>',
            );
        }

        if ($max && $current >= $max) {
            return new HtmlString(
                '<span style="color: #f59e0b; font-weight: bold;">🟠 Overstocked</span>',
            );
        }

        return new HtmlString(
            '<span style="color: #10b981; font-weight: bold;">🟢 Normal</span>',
        );
    }

    private static function getStockPercentage(callable $get): HtmlString|string
    {
        $current = (float) ($get('current_stock') ?? 0);
        $min = (float) ($get('min_stock_level') ?? 1);
        $max = (float) ($get('max_stock_level') ?? $current);

        if ($max === 0) {
            return new HtmlString('<span style="color: #6b7280;">0%</span>');
        }

        // Calculate percentage between min and max
        $percentage = (($current - $min) / ($max - $min)) * 100;
        $percentage = max(0, min(100, $percentage));

        $color = match (true) {
            $percentage < 20 => '#dc2626',
            $percentage < 50 => '#f59e0b',
            default => '#10b981',
        };

        return new HtmlString(
            "<span style='color: {$color}; font-weight: bold;'>{$percentage}%</span>",
        );
    }

    private static function getDaysUntilReorder(
        callable $get,
    ): HtmlString|string {
        $current = (float) ($get('current_stock') ?? 0);
        $reorder = (float) ($get('reorder_level') ?? 0);

        if ($current <= $reorder) {
            return new HtmlString(
                '<span style="color: #dc2626; font-weight: bold;">Now</span>',
            );
        }

        // This would typically use historical usage data
        // For now, we'll estimate based on a simple calculation
        $daysUntilReorder = 30; // Placeholder

        return new HtmlString(
            "<span style='color: #10b981;'>{$daysUntilReorder} days</span>",
        );
    }

    private static function getRecommendedOrder(
        callable $get,
    ): HtmlString|string {
        $current = (float) ($get('current_stock') ?? 0);
        $max = (float) ($get('max_stock_level') ?? 1000);
        $reorder = (float) ($get('reorder_level') ?? 100);

        if ($current > $reorder) {
            return new HtmlString(
                '<span style="color: #6b7280;">Not needed</span>',
            );
        }

        $recommended = $max - $current;

        return new HtmlString(
            "<span style='color: #3b82f6; font-weight: bold;'>".
                number_format($recommended, 2).
                '</span>',
        );
    }

    private static function calculateTotalValue(
        callable $get,
    ): HtmlString|string {
        $current = (float) ($get('current_stock') ?? 0);
        $ingredientId = $get('ingredient_id');

        if (! $ingredientId) {
            return new HtmlString('<span style="color: #6b7280;">$0.00</span>');
        }

        $ingredient = \App\Models\Ingredient::find($ingredientId);
        if (! $ingredient) {
            return new HtmlString('<span style="color: #6b7280;">$0.00</span>');
        }

        $totalValue = $current * $ingredient->unit_cost;

        return self::formatInventoryValue($current, $ingredient->unit_cost);
    }

    private static function getCostPerUnit(callable $get): HtmlString|string
    {
        $ingredientId = $get('ingredient_id');

        if (! $ingredientId) {
            return new HtmlString('<span style="color: #6b7280;">$0.00</span>');
        }

        $ingredient = \App\Models\Ingredient::find($ingredientId);
        if (! $ingredient) {
            return new HtmlString('<span style="color: #6b7280;">$0.00</span>');
        }

        return self::formatUnitCost($ingredient->unit_cost);
    }

    private static function getTurnoverRate(callable $get): HtmlString|string
    {
        // This would typically be calculated from historical usage data
        // For now, we'll provide a placeholder
        $current = (float) ($get('current_stock') ?? 0);
        $max = (float) ($get('max_stock_level') ?? 1000);

        if ($current === 0) {
            return new HtmlString('<span style="color: #6b7280;">N/A</span>');
        }

        // Simple turnover calculation (current/max)
        $turnover = ($current / $max) * 100;

        $color = match (true) {
            $turnover > 80 => '#10b981',
            $turnover > 50 => '#f59e0b',
            default => '#dc2626',
        };

        return new HtmlString(
            "<span style='color: {$color};'>{$turnover}%</span>",
        );
    }
}
