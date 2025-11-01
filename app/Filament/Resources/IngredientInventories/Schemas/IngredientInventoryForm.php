<?php

declare(strict_types=1);

namespace App\Filament\Resources\IngredientInventories\Schemas;

use App\Enums\UnitType;
use App\Filament\Concerns\CurrencyAware;
use App\Models\Ingredient;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                            Toggle::make('create_new_ingredient')
                                ->label('Create New Ingredient')
                                ->helperText('Toggle to create a new ingredient instead of selecting an existing one')
                                ->reactive()
                                ->afterStateUpdated(
                                    fn ($state, callable $set) => $set('ingredient_id', null)
                                )
                                ->default(fn ($record) => $record ? false : null)
                                ->columnSpan(2),

                            // Existing ingredient selection
                            Select::make('ingredient_id')
                                ->label('Existing Ingredient')
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
                                    self::populateIngredientDefaults(...),
                                )
                                ->hidden(fn (callable $get) => $get('create_new_ingredient'))
                                ->columnSpan(2),

                            // New ingredient creation fields
                            TextInput::make('new_ingredient_name')
                                ->label('Ingredient Name')
                                ->required()
                                ->maxLength(100)
                                ->placeholder('e.g., Arabica Coffee Beans')
                                ->helperText('Enter the full name of the ingredient')
                                ->hidden(fn (callable $get) => ! $get('create_new_ingredient'))
                                ->requiredWith('create_new_ingredient')
                                ->columnSpan(1),

                            Select::make('new_ingredient_unit_type')
                                ->label('Unit of Measurement')
                                ->required()
                                ->options(fn () => UnitType::getOptions())
                                ->native(false)
                                ->searchable()
                                ->preload()
                                ->placeholder('Select unit type')
                                ->helperText('How this ingredient is measured and tracked')
                                ->hidden(fn (callable $get) => ! $get('create_new_ingredient'))
                                ->requiredWith('create_new_ingredient')
                                ->columnSpan(1),

                            TextInput::make('current_stock')
                                ->label('Current Stock')
                                ->required()
                                ->numeric()
                                ->step(0.001)
                                ->minValue(0)
                                ->placeholder('e.g., 5000')
                                ->helperText('Current available quantity')
                                ->prefixIcon('heroicon-o-cube')
                                ->suffix(self::getUnitSuffix(...))
                                ->reactive()
                                ->afterStateUpdated(
                                    fn (
                                        $state,
                                        callable $get,
                                        callable $set,
                                    ) => self::updateStockStatus(
                                        $get,
                                    ),
                                )
                                ->columnSpan(1),
                        ]),

                        Grid::make(2)->schema([
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
                                ->suffix(self::getUnitSuffix(...))
                                ->columnSpan(1),

                            TextInput::make('min_stock_level')
                                ->label('Minimum Stock')
                                ->numeric()
                                ->step(0.001)
                                ->minValue(0)
                                ->placeholder('e.g., 100')
                                ->helperText('Minimum acceptable stock level')
                                ->prefixIcon('heroicon-o-arrow-down')
                                ->suffix(self::getUnitSuffix(...))
                                ->columnSpan(1),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('max_stock_level')
                                ->label('Maximum Stock')
                                ->numeric()
                                ->step(0.001)
                                ->minValue(0)
                                ->placeholder('e.g., 10000')
                                ->helperText('Maximum stock capacity')
                                ->prefixIcon('heroicon-o-arrow-up')
                                ->suffix(self::getUnitSuffix(...))
                                ->columnSpan(1),

                            TextInput::make('location')
                                ->label('Storage Location')
                                ->placeholder(
                                    'e.g., Main Storage, Fridge A, Freezer B1',
                                )
                                ->helperText(
                                    'Physical location where this ingredient is stored',
                                )
                                ->prefixIcon('heroicon-o-map-pin')
                                ->columnSpan(1),
                        ]),

                        Grid::make(2)->schema([
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
                                            self::getStockStatusDisplay(...),
                                        )
                                        ->columnSpan(1),

                                    Placeholder::make('stock_percentage')
                                        ->label('Stock Level')
                                        ->content(
                                            self::getStockPercentage(...),
                                        )
                                        ->columnSpan(1),

                                    Placeholder::make('days_until_reorder')
                                        ->label('Days Until Reorder')
                                        ->content(
                                            self::getDaysUntilReorder(...),
                                        )
                                        ->columnSpan(1),

                                    Placeholder::make('recommended_order')
                                        ->label('Recommended Order')
                                        ->content(
                                            self::getRecommendedOrder(...),
                                        )
                                        ->columnSpan(1),
                                ]),
                            ]),

                        Section::make('Ingredient Details')
                            ->description('View and manage ingredient basic information')
                            ->icon('heroicon-o-cube')
                            ->collapsible()
                            ->schema([
                                Grid::make(2)->schema([
                                    Placeholder::make('ingredient_name')
                                        ->label('Ingredient Name')
                                        ->content(
                                            self::getIngredientName(...),
                                        )
                                        ->columnSpan(1),

                                    Placeholder::make('unit_type')
                                        ->label('Unit of Measurement')
                                        ->content(
                                            self::getUnitType(...),
                                        )
                                        ->columnSpan(1),
                                ]),
                            ]),

                    ])
                    ->columns(1),
            ]);

    }

    private static function populateIngredientDefaults(
        mixed $ingredientId,
        callable $set,
    ): void {
        if (! $ingredientId) {
            return;
        }

        $ingredient = Ingredient::query()->find($ingredientId);
        if (! $ingredient) {
            return;
        }

        // Set default values based on ingredient type
        $set('min_stock_level', 100);
        $set('max_stock_level', 10000);
        $set('reorder_level', 500);

        // If ingredient has existing inventory, populate those values
        $existingInventory = $ingredient instanceof Ingredient ? $ingredient->inventory : null;
        if ($existingInventory instanceof \App\Models\IngredientInventory) {
            $set('current_stock', (float) $existingInventory->current_stock);
            $set('min_stock_level', (float) $existingInventory->min_stock_level);
            $set('max_stock_level', (float) $existingInventory->max_stock_level);
            $set('reorder_level', (float) $existingInventory->reorder_level);
            $set('location', (string) $existingInventory->location);
            $set('supplier_info', (string) $existingInventory->supplier_info);
        }
    }

    private static function getUnitSuffix(callable $get): ?string
    {
        $ingredientId = $get('ingredient_id');
        $isNewIngredient = $get('create_new_ingredient');
        $newUnitTypeValue = $get('new_ingredient_unit_type');

        if ($isNewIngredient && is_string($newUnitTypeValue)) {
            $unitType = UnitType::tryFrom($newUnitTypeValue);

            return $unitType?->getLabel();
        }

        if (! $ingredientId) {
            return null;
        }

        $ingredient = Ingredient::query()->find($ingredientId);
        if (! $ingredient instanceof Ingredient) {
            return null;
        }

        return $ingredient->unit_type->getLabel();
    }

    private static function updateStockStatus(
        callable $get,
    ): void {
        $get('min_stock_level') ?? 0;
        $get('max_stock_level') ?? PHP_FLOAT_MAX;
        // Could trigger notifications or other actions here
    }

    private static function getStockStatusDisplay(
        callable $get,
        mixed $record,
    ): HtmlString {
        $current = is_numeric($get('current_stock')) ? (float) $get('current_stock') : 0.0;
        $min = is_numeric($get('min_stock_level')) ? (float) $get('min_stock_level') : 0.0;
        $max = is_numeric($get('max_stock_level')) ? (float) $get('max_stock_level') : PHP_FLOAT_MAX;

        if (! $record && $current === 0.0) {
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

    private static function getStockPercentage(callable $get): HtmlString
    {
        $current = is_numeric($get('current_stock')) ? (float) $get('current_stock') : 0.0;
        $min = is_numeric($get('min_stock_level')) ? (float) $get('min_stock_level') : 1.0;
        $max = is_numeric($get('max_stock_level')) ? (float) $get('max_stock_level') : $current;

        if ($max === 0.0) {
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
    ): HtmlString {
        $current = is_numeric($get('current_stock')) ? (float) $get('current_stock') : 0.0;
        $reorder = is_numeric($get('reorder_level')) ? (float) $get('reorder_level') : 0.0;

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
    ): HtmlString {
        $current = is_numeric($get('current_stock')) ? (float) $get('current_stock') : 0.0;
        $max = is_numeric($get('max_stock_level')) ? (float) $get('max_stock_level') : 1000.0;
        $reorder = is_numeric($get('reorder_level')) ? (float) $get('reorder_level') : 100.0;

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

    private static function getIngredientName(callable $get): HtmlString
    {
        $isNewIngredient = $get('create_new_ingredient');
        $newIngredientName = $get('new_ingredient_name');
        $ingredientId = $get('ingredient_id');

        if ($isNewIngredient && $newIngredientName) {
            return new HtmlString(
                "<span style='color: #374151; font-weight: 600;'>".htmlspecialchars(is_string($newIngredientName) ? $newIngredientName : '').'</span>'
            );
        }

        if (! $ingredientId) {
            return new HtmlString('<span style="color: #6b7280;">Not selected</span>');
        }

        $ingredient = Ingredient::query()->find($ingredientId);
        if (! $ingredient instanceof Ingredient) {
            return new HtmlString('<span style="color: #6b7280;">Not found</span>');
        }

        return new HtmlString(
            "<span style='color: #374151; font-weight: 600;'>{$ingredient->name}</span>",
        );
    }

    private static function getUnitType(callable $get): HtmlString
    {
        $isNewIngredient = $get('create_new_ingredient');
        $newUnitTypeValue = $get('new_ingredient_unit_type');
        $ingredientId = $get('ingredient_id');

        if ($isNewIngredient && is_string($newUnitTypeValue)) {
            $newUnitType = UnitType::tryFrom($newUnitTypeValue);
            if ($newUnitType) {
                return new HtmlString(
                    "<span style='color: #6b7280;'>{$newUnitType->getLabel()}</span>"
                );
            }

            return new HtmlString('<span style="color: #6b7280;">Not selected</span>');
        }

        if (! $ingredientId) {
            return new HtmlString('<span style="color: #6b7280;">-</span>');
        }

        $ingredient = Ingredient::query()->find($ingredientId);
        if (! $ingredient instanceof Ingredient) {
            return new HtmlString('<span style="color: #6b7280;">-</span>');
        }

        return new HtmlString(
            "<span style='color: #6b7280;'>{$ingredient->unit_type->getLabel()}</span>"
        );
    }
}
