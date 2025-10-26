<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductIngredients\Schemas;

use App\Filament\Concerns\CurrencyAware;
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
                                ->suffix(fn ($get) => self::getUnitSuffix($get))
                                ->columnSpan(1),
                            Placeholder::make('unit_info')
                                ->label('Measurement Unit')
                                ->content(fn ($get) => self::getUnitInfo($get))
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
                                    ->content(function ($get) {
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

                                        $ingredient = \App\Models\Ingredient::find(
                                            $ingredientId,
                                        );
                                        if (! $ingredient) {
                                            return new HtmlString(
                                                '<span style="color: #6b7280;">Ingredient not found</span>',
                                            );
                                        }

                                        $cost =
                                            $quantity * $ingredient->unit_cost;
                                        $unit = $ingredient->unit_type;

                                        return static::formatCostCalculation(
                                            $quantity,
                                            $ingredient->unit_cost,
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
                                        ->content(function ($get) {
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

                                            $ingredient = \App\Models\Ingredient::with(
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
                                        ->content(function ($get) {
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

                                            $ingredient = \App\Models\Ingredient::with(
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
                                                $ingredient->current_stock;
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

        $ingredient = \App\Models\Ingredient::find($ingredientId);

        if (! $ingredient) {
            return null;
        }

        return $ingredient->unit_type;
    }

    private static function getUnitInfo(callable $get): HtmlString|string
    {
        $ingredientId = $get('ingredient_id');

        if (! $ingredientId) {
            return 'Select ingredient first';
        }

        $ingredient = \App\Models\Ingredient::find($ingredientId);

        if (! $ingredient) {
            return 'Ingredient not found';
        }

        return new HtmlString(
            "<span style='color: #374151; font-weight: 500;'>{$ingredient->unit_type}</span>",
        );
    }
}
