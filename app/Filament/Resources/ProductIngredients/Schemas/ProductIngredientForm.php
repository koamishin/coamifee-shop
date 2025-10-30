<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductIngredients\Schemas;

use App\Enums\UnitType;
use App\Filament\Concerns\CurrencyAware;
use App\Models\Ingredient;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

final class ProductIngredientForm
{
    use CurrencyAware;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Product Recipe Configuration')
                    ->description(
                        'Define ingredient requirements and quantities for this product',
                    )
                    ->icon('heroicon-o-beaker')
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
                                    'Choose the product to configure ingredients for',
                                )
                                ->reactive()
                                ->afterStateUpdated(
                                    fn ($state, callable $set) => $set(
                                        'quantity_required',
                                        null,
                                    ),
                                )
                                ->columnSpan(1),
                            Select::make('ingredient_id')
                                ->label('Ingredient')
                                ->relationship('ingredient', 'name')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->placeholder('Select an ingredient')
                                ->helperText(
                                    'Choose the ingredient to add to this product',
                                )
                                ->reactive()
                                ->afterStateUpdated(
                                    fn ($state, callable $set) => $set(
                                        'quantity_required',
                                        null,
                                    ),
                                )
                                ->columnSpan(1),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('quantity_required')
                                ->label('Quantity Required')
                                ->required()
                                ->numeric()
                                ->step(0.001)
                                ->minValue(0.001)
                                ->placeholder('e.g., 18.50')
                                ->helperText(
                                    'Amount of this ingredient needed per product',
                                )
                                ->suffix(self::getUnitSuffix(...))
                                ->columnSpan(1),
                            Placeholder::make('unit_info')
                                ->label('Measurement Unit')
                                ->content(self::getUnitInfo(...))
                                ->columnSpan(1),
                        ]),

                        Section::make('Cost Analysis')
                            ->description(
                                'Calculate the cost impact of this ingredient on the product',
                            )
                            ->icon('heroicon-o-currency-dollar')
                            ->collapsible()
                            ->schema([
                                Placeholder::make('cost_calculation')
                                    ->label('Cost per Product')
                                    ->content(function ($get): HtmlString {
                                        $quantity =
                                            (float) ($get(
                                                'quantity_required',
                                            ) ?? 0);
                                        $ingredientId = $get('ingredient_id');

                                        if (! $quantity || ! $ingredientId) {
                                            return new HtmlString(
                                                '<span style="color: #6b7280;">Select ingredient and quantity to see cost</span>',
                                            );
                                        }

                                        $ingredient = Ingredient::query()->find($ingredientId);
                                        if (! $ingredient) {
                                            return new HtmlString(
                                                '<span style="color: #6b7280;">Ingredient not found</span>',
                                            );
                                        }

                                        $unitCost = $ingredient->unit_cost ?? 0;
                                        $cost = $quantity * $unitCost;
                                        $unit = $ingredient->unit_type;

                                        return self::formatCostCalculation(
                                            $quantity,
                                            $unitCost,
                                            $unit,
                                        );
                                    })
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Inventory Analysis')
                            ->description(
                                'Analyze stock levels and production capacity',
                            )
                            ->icon('heroicon-o-cube')
                            ->collapsible()
                            ->schema([
                                Grid::make(2)->schema([
                                    Placeholder::make('total_products_possible')
                                        ->label(
                                            'Products Possible with Current Stock',
                                        )
                                        ->content(function ($get): string|HtmlString {
                                            $quantityRequired =
                                                (float) ($get(
                                                    'quantity_required',
                                                ) ?? 0);
                                            $ingredientId = $get(
                                                'ingredient_id',
                                            );

                                            if (
                                                ! $quantityRequired ||
                                                ! $ingredientId
                                            ) {
                                                return '-';
                                            }

                                            $ingredient = Ingredient::with(
                                                'inventory',
                                            )->find($ingredientId);
                                            if (
                                                ! $ingredient ||
                                                ! $ingredient->inventory
                                            ) {
                                                return 'N/A';
                                            }

                                            $currentStock =
                                                $ingredient->inventory
                                                    ->current_stock;
                                            $productsPossible = floor(
                                                $currentStock /
                                                    $quantityRequired,
                                            );

                                            $color =
                                                $productsPossible <= 10
                                                    ? '#dc2626'
                                                    : ($productsPossible <= 50
                                                        ? '#f59e0b'
                                                        : '#10b981');

                                            return new HtmlString(
                                                "<span style='color: $color; font-weight: bold; font-size: 1.1em;'>".
                                                    number_format(
                                                        $productsPossible,
                                                    ).
                                                    '</span>',
                                            );
                                        }),
                                    Placeholder::make('low_stock_warning')
                                        ->label('Stock Status')
                                        ->content(function ($get): HtmlString {
                                            $quantityRequired =
                                                (float) ($get(
                                                    'quantity_required',
                                                ) ?? 0);
                                            $ingredientId = $get(
                                                'ingredient_id',
                                            );

                                            if (
                                                ! $quantityRequired ||
                                                ! $ingredientId
                                            ) {
                                                return new HtmlString(
                                                    '<span style="color: #6b7280;">⚪ Unknown</span>',
                                                );
                                            }

                                            $ingredient = Ingredient::with(
                                                'inventory',
                                            )->find($ingredientId);
                                            if (
                                                ! $ingredient ||
                                                ! $ingredient->inventory
                                            ) {
                                                return new HtmlString(
                                                    '<span style="color: #dc2626;">🔴 No Inventory Set</span>',
                                                );
                                            }

                                            $currentStock =
                                                $ingredient->inventory->current_stock;
                                            $inventory = $ingredient->inventory;
                                            $minStock =
                                                $inventory->min_stock_level ??
                                                0;
                                            $productsPossible = floor(
                                                $currentStock /
                                                    $quantityRequired,
                                            );

                                            if ($currentStock <= $minStock) {
                                                return new HtmlString(
                                                    '<span style="color: #dc2626;">🔴 Low Stock</span>',
                                                );
                                            }

                                            if ($productsPossible <= 10) {
                                                return new HtmlString(
                                                    '<span style="color: #f59e0b;">🟠 Limited Stock</span>',
                                                );
                                            }

                                            return new HtmlString(
                                                '<span style="color: #10b981;">🟢 Good Stock</span>',
                                            );
                                        }),
                                ]),
                            ]),
                    ]),
            ])
            ->columns(1);
    }

    private static function getUnitSuffix(callable $get): ?string
    {
        $ingredientId = $get('ingredient_id');

        if (! $ingredientId) {
            return null;
        }

        $ingredient = Ingredient::query()->find($ingredientId);

        if (! $ingredient) {
            return null;
        }

        return $ingredient->unit_type?->getLabel() ?? $ingredient->unit_type;
    }

    private static function getUnitInfo(callable $get): HtmlString|string
    {
        $ingredientId = $get('ingredient_id');

        if (! $ingredientId) {
            return 'Select ingredient first';
        }

        $ingredient = Ingredient::query()->find($ingredientId);

        if (! $ingredient) {
            return 'Ingredient not found';
        }

        $unitType = $ingredient->unit_type;
        $label = $unitType?->getLabel() ?? $unitType;
        $icon = $unitType?->getIcon() ?? 'heroicon-o-cube';
        $color = $unitType?->getColor() ?? 'gray';
        $description = $unitType?->getDescription() ?? 'Measurement unit';

        return new HtmlString("
            <div style='display: flex; align-items: center; gap: 8px;'>
                <span style='color: #6b7280;'>
                    <svg class='w-4 h-4' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'></path>
                    </svg>
                </span>
                <div>
                    <div style='color: #374151; font-weight: 600;'>{$label}</div>
                    <div style='color: #6b7280; font-size: 0.85em;'>{$description}</div>
                </div>
            </div>
        ");
    }

    private static function formatCostCalculation(float $quantity, float $unitCost, $unitType): HtmlString
    {
        $totalCost = $quantity * $unitCost;
        $unitLabel = $unitType?->getLabel() ?? 'unit';

        return new HtmlString("
            <div style='padding: 16px; background: #f9fafb; border-radius: 8px; border-left: 4px solid #10b981;'>
                <div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;'>
                    <span style='color: #6b7280; font-size: 0.9em;'>Calculation:</span>
                    <span style='color: #374151; font-weight: 500;'>
                        {$quantity} {$unitLabel} × {$unitCost} = {$totalCost}
                    </span>
                </div>
                <div style='display: flex; justify-content: space-between; align-items: center;'>
                    <span style='color: #111827; font-weight: 600; font-size: 1.1em;'>Cost per Product:</span>
                    <span style='color: #10b981; font-weight: bold; font-size: 1.2em;'>" .
                        number_format($totalCost, 2) .
                    "</span>
                </div>
            </div>
        ");
    }
}
