<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductIngredients\Tables;

use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

final class ProductIngredientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label('Product')
                    ->description('Product name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->limit(30),
                TextColumn::make('ingredient.name')
                    ->label('Ingredient')
                    ->description('Ingredient name')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                TextColumn::make('quantity_required')
                    ->label('Quantity')
                    ->description('Amount required per product')
                    ->numeric(decimalPlaces: 3)
                    ->sortable()
                    ->formatStateUsing(fn ($state, $record) => new HtmlString("
                        <div style='display: flex; align-items: center; justify-content: flex-end; gap: 4px;'>
                            <span>{$state}</span>
                            <span style='color: #6b7280; font-size: 0.85em;'>{$record->ingredient->unit_type}</span>
                        </div>
                    ")),
                TextColumn::make('cost_per_product')
                    ->label('Cost/Unit')
                    ->description('Ingredient cost per product')
                    ->money('USD')
                    ->sortable()
                    ->formatStateUsing(fn ($record) => $record->quantity_required * $record->ingredient->unit_cost),
                TextColumn::make('ingredient.current_stock')
                    ->label('Current Stock')
                    ->description('Available stock level')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->formatStateUsing(fn ($record) => self::formatStock($record)),
                IconColumn::make('stock_status')
                    ->label('Status')
                    ->icon(fn ($record) => self::getStockStatusIcon($record))
                    ->color(fn ($record) => self::getStockStatusColor($record))
                    ->tooltip(fn ($record) => self::getStockStatusTooltip($record)),
                TextColumn::make('products_possible')
                    ->label('Products Possible')
                    ->description('How many products can be made with current stock')
                    ->numeric(decimalPlaces: 0)
                    ->sortable()
                    ->formatStateUsing(fn ($record) => self::calculateProductsPossible($record)),
            ])
            ->defaultSort('product.name')
            ->filters([
                SelectFilter::make('product_id')
                    ->label('Product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('ingredient_id')
                    ->label('Ingredient')
                    ->relationship('ingredient', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('stock_status')
                    ->label('Stock Status')
                    ->options([
                        'low' => 'Low Stock',
                        'limited' => 'Limited Stock',
                        'good' => 'Good Stock',
                        'no_inventory' => 'No Inventory Set',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('view_ingredient')
                    ->label('Ingredient')
                    ->icon('heroicon-o-cube')
                    ->url(fn ($record) => route('filament.admin.resources.ingredients.view', $record->ingredient))
                    ->openUrlInNewTab(),
                Action::make('view_product')
                    ->label('Product')
                    ->icon('heroicon-o-shopping-bag')
                    ->url(fn ($record) => route('filament.admin.resources.products.edit', $record->product))
                    ->openUrlInNewTab()
                    ->hidden(fn ($record) => ! $record->product),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->emptyStateHeading('No product ingredients found')
            ->emptyStateDescription('No ingredients have been configured for products yet')
            ->emptyStateActions([
                Action::make('create_first')
                    ->label('Create Product Ingredient')
                    ->icon('heroicon-o-plus')
                    ->url(route('filament.admin.resources.product-ingredients.create')),
            ]);
    }

    private static function formatStock($record): string
    {
        if (! $record->ingredient->is_trackable) {
            return '<span style="color: #6b7280;">Not Tracked</span>';
        }

        $stock = (float) $record->ingredient->current_stock;

        $inventory = $record->ingredient->inventory;
        $min = $inventory->min_stock_level ?? 0;
        $max = $inventory->max_stock_level ?? PHP_FLOAT_MAX;

        $color = '#dc2626'; // red
        if ($stock > $min && $stock < $max) {
            $color = '#10b981'; // green
        } elseif ($stock >= $max) {
            $color = '#f59e0b'; // orange
        }

        return "<span style='color: {$color}; font-weight: 500;'>".number_format($stock, 2).'</span>';
    }

    private static function getStockStatusIcon($record): string
    {
        if (! $record->ingredient->is_trackable) {
            return 'heroicon-o-x-circle';
        }

        $stock = (float) $record->ingredient->current_stock;

        $inventory = $record->ingredient->inventory;
        $min = $inventory->min_stock_level ?? 0;
        $max = $inventory->max_stock_level ?? PHP_FLOAT_MAX;
        $productsPossible = floor($stock / $record->quantity_required);

        if ($productsPossible <= 5) {
            return 'heroicon-o-x-circle';
        }
        if ($productsPossible <= 20) {
            return 'heroicon-o-exclamation-triangle';
        }

        return 'heroicon-o-check-circle';
    }

    private static function getStockStatusColor($record): string
    {
        if (! $record->ingredient->is_trackable) {
            return 'gray';
        }

        $stock = (float) $record->ingredient->current_stock;
        $productsPossible = floor($stock / $record->quantity_required);

        if ($productsPossible <= 5) {
            return 'danger';
        }
        if ($productsPossible <= 20) {
            return 'warning';
        }

        return 'success';
    }

    private static function getStockStatusTooltip($record): string
    {
        if (! $record->ingredient->is_trackable) {
            return 'This ingredient is not tracked for stock levels';
        }

        $stock = (float) $record->ingredient->current_stock;
        $productsPossible = floor($stock / $record->quantity_required);

        if ($productsPossible <= 5) {
            return "Low Stock: Only {$productsPossible} products possible";
        }
        if ($productsPossible <= 20) {
            return "Limited Stock: {$productsPossible} products possible";
        }

        return "Good Stock: {$productsPossible} products possible";
    }

    private static function calculateProductsPossible($record): string
    {
        if (! $record->ingredient->is_trackable) {
            return '-';
        }

        $stock = (float) $record->ingredient->current_stock;
        $productsPossible = floor($stock / $record->quantity_required);

        $color = '#dc2626'; // red
        if ($productsPossible > 20) {
            $color = '#10b981'; // green
        } elseif ($productsPossible > 5) {
            $color = '#f59e0b'; // orange
        }

        return "<span style='color: {$color}; font-weight: bold;'>".number_format($productsPossible).'</span>';
    }
}
