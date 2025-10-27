<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductIngredients\Tables;

use App\Enums\UnitType;
use App\Filament\Concerns\CurrencyAware;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

final class ProductIngredientsTable
{
    use CurrencyAware;

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
                    ->limit(30)
                    ->icon('heroicon-o-shopping-bag'),

                TextColumn::make('ingredient.name')
                    ->label('Ingredient')
                    ->description('Ingredient name')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->icon('heroicon-o-cube'),

                TextColumn::make('quantity_required')
                    ->label('Quantity')
                    ->description('Amount required per product')
                    ->numeric(decimalPlaces: 3)
                    ->sortable()
                    ->alignRight()
                    ->formatStateUsing(
                        fn ($state, $record): HtmlString => new HtmlString("
                        <div style='display: flex; align-items: center; justify-content: flex-end; gap: 4px;'>
                            <span style='font-weight: 600;'>{$state}</span>
                            <span style='color: #6b7280; font-size: 0.85em;'>{$record->ingredient->unit_type->getLabel()}</span>
                        </div>
                    "),
                    ),

                TextColumn::make('cost_per_product')
                    ->label('Cost/Product')
                    ->description('Ingredient cost per product')
                    ->money(self::getMoneyConfig())
                    ->sortable()
                    ->alignRight()
                    ->formatStateUsing(
                        fn ($record): int|float => $record->quantity_required *
                            $record->ingredient->unit_cost,
                    )
                    ->color('success'),

                TextColumn::make('ingredient.current_stock')
                    ->label('Stock')
                    ->description('Available stock level')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->alignRight()
                    ->formatStateUsing(
                        self::formatStock(...),
                    ),

                IconColumn::make('stock_status')
                    ->label('Status')
                    ->icon(self::getStockStatusIcon(...))
                    ->color(self::getStockStatusColor(...))
                    ->tooltip(
                        self::getStockStatusTooltip(...),
                    ),

                TextColumn::make('products_possible')
                    ->label('Can Make')
                    ->description('Products possible with current stock')
                    ->numeric(decimalPlaces: 0)
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color(
                        self::getProductsPossibleColor(...),
                    )
                    ->formatStateUsing(
                        self::calculateProductsPossible(...),
                    ),

                TextColumn::make('created_at')
                    ->label('Added')
                    ->description('Date added to recipe')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('product.name')
            ->striped()
            ->filters([
                SelectFilter::make('product_id')
                    ->label('Product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Filter by product'),

                SelectFilter::make('ingredient_id')
                    ->label('Ingredient')
                    ->relationship('ingredient', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Filter by ingredient'),

                SelectFilter::make('stock_status')
                    ->label('Stock Status')
                    ->options([
                        'low' => '🔴 Low Stock',
                        'limited' => '🟠 Limited Stock',
                        'good' => '🟢 Good Stock',
                        'no_inventory' => '⚪ No Inventory Set',
                    ])
                    ->placeholder('Filter by stock status'),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('View Details')
                        ->icon('heroicon-o-eye'),

                    EditAction::make()
                        ->label('Edit Recipe')
                        ->icon('heroicon-o-pencil'),

                    Action::make('duplicate')
                        ->label('Duplicate')
                        ->icon(Heroicon::DocumentDuplicate)
                        ->action(function ($record): void {
                            $newRecord = $record->replicate();
                            $newRecord->save();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Duplicate Recipe Item')
                        ->modalDescription(
                            'Create a copy of this product ingredient configuration.',
                        )
                        ->modalSubmitActionLabel('Yes, duplicate it'),

                    Action::make('view_ingredient')
                        ->label('View Ingredient')
                        ->icon('heroicon-o-cube')
                        ->url(
                            fn ($record): string => route(
                                'filament.admin.resources.ingredients.view',
                                $record->ingredient,
                            ),
                        )
                        ->openUrlInNewTab(),

                    Action::make('view_product')
                        ->label('View Product')
                        ->icon('heroicon-o-shopping-bag')
                        ->url(
                            fn ($record): string => route(
                                'filament.admin.resources.products.edit',
                                $record->product,
                            ),
                        )
                        ->openUrlInNewTab()
                        ->hidden(fn ($record): bool => ! $record->product),

                    DeleteAction::make()
                        ->label('Remove from Recipe')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->modalHeading('Remove from Recipe')
                        ->modalDescription(
                            'Are you sure you want to remove this ingredient from the product recipe?',
                        )
                        ->modalSubmitActionLabel('Yes, remove it'),
                ])
                    ->label('Actions')
                    ->icon('heroicon-o-ellipsis-horizontal')
                    ->color('primary'),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('Remove Selected')
                    ->requiresConfirmation()
                    ->modalHeading('Remove Selected Ingredients')
                    ->modalDescription(
                        'Are you sure you want to remove the selected ingredients from their product recipes?',
                    )
                    ->modalSubmitActionLabel('Yes, remove them'),
            ])
            ->emptyStateHeading('No product ingredients found')
            ->emptyStateDescription(
                'No ingredients have been configured for products yet',
            )
            ->emptyStateActions([
                Action::make('create_first')
                    ->label('Create Product Ingredient')
                    ->icon('heroicon-o-plus')
                    ->url(
                        route(
                            'filament.admin.resources.product-ingredients.create',
                        ),
                    ),
            ])
            ->poll('60s'); // Refresh every minute for real-time stock updates
    }

    private static function formatStock($record): string
    {
        $inventory = $record->ingredient->inventory;
        if (! $inventory) {
            return '<span style="color: #6b7280;">No Inventory</span>';
        }

        $stock = (float) $inventory->current_stock;
        $min = $inventory->min_stock_level ?? 0;
        $max = $inventory->max_stock_level ?? PHP_FLOAT_MAX;

        $color = '#dc2626'; // red
        if ($stock > $min && $stock < $max) {
            $color = '#10b981'; // green
        } elseif ($stock >= $max) {
            $color = '#f59e0b'; // orange
        }

        return "<span style='color: {$color}; font-weight: 500;'>" .
            number_format($stock, 2) .
            '</span>';
    }

    private static function getStockStatusIcon($record): string
    {
        $inventory = $record->ingredient->inventory;
        if (! $inventory) {
            return 'heroicon-o-x-circle';
        }

        $stock = (float) $inventory->current_stock;
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
        $inventory = $record->ingredient->inventory;
        if (! $inventory) {
            return 'gray';
        }

        $stock = (float) $inventory->current_stock;
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
        $inventory = $record->ingredient->inventory;
        if (! $inventory) {
            return 'No inventory data available for this ingredient';
        }

        $stock = (float) $inventory->current_stock;
        $productsPossible = floor($stock / $record->quantity_required);

        if ($productsPossible <= 5) {
            return "Low Stock: Only {$productsPossible} products possible";
        }
        if ($productsPossible <= 20) {
            return "Limited Stock: {$productsPossible} products possible";
        }

        return "Good Stock: {$productsPossible} products possible";
    }

    private static function getProductsPossibleColor($record): string
    {
        $inventory = $record->ingredient->inventory;
        if (! $inventory) {
            return 'gray';
        }

        $stock = (float) $inventory->current_stock;
        $productsPossible = floor($stock / $record->quantity_required);

        if ($productsPossible <= 5) {
            return 'danger';
        }
        if ($productsPossible <= 20) {
            return 'warning';
        }

        return 'success';
    }

    private static function calculateProductsPossible($record): string
    {
        $inventory = $record->ingredient->inventory;
        if (! $inventory) {
            return '-';
        }

        $stock = (float) $inventory->current_stock;
        $productsPossible = floor($stock / $record->quantity_required);

        return (string) $productsPossible;
    }
}
