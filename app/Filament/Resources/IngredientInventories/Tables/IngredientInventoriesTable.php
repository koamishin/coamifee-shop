<?php

declare(strict_types=1);

namespace App\Filament\Resources\IngredientInventories\Tables;

use App\Enums\UnitType;
use App\Filament\Concerns\CurrencyAware;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class IngredientInventoriesTable
{
    use CurrencyAware;

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ingredient.name')
                    ->label('Ingredient')
                    ->description('Basic ingredient information')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->grow(),

                TextColumn::make('ingredient.unit_type')
                    ->label('Unit')
                    ->description('Measurement unit')
                    ->badge()
                    ->color(fn ($state) => $state?->getColor() ?? 'gray')
                    ->icon(fn ($state) => $state?->getIcon())
                    ->formatStateUsing(fn ($state) => $state?->getLabel())
                    ->searchable()
                    ->sortable(),

                TextColumn::make('current_stock')
                    ->label('Current Stock')
                    ->description('Current available quantity')
                    ->numeric(decimalPlaces: 3, thousandsSeparator: ',')
                    ->sortable()
                    ->alignRight()
                    ->weight('medium')
                    ->color(self::getStockColor(...))
                    ->icon('heroicon-o-cube'),

                TextColumn::make('min_stock_level')
                    ->label('Min Stock')
                    ->description('Minimum acceptable level')
                    ->numeric(decimalPlaces: 3, thousandsSeparator: ',')
                    ->sortable()
                    ->alignRight()
                    ->color('danger')
                    ->icon('heroicon-o-arrow-down'),

                TextColumn::make('unit_cost')
                    ->label('Cost/Unit')
                    ->description('Cost per unit')
                    ->money(self::getMoneyConfig())
                    ->sortable()
                    ->alignRight()
                    ->placeholder('Not set')
                    ->icon('heroicon-o-tag'),
            ])
            ->filters([
                SelectFilter::make('ingredient.unit_type')
                    ->label('Unit Type')
                    ->options(UnitType::getOptions()),

                Filter::make('low_stock')
                    ->label('Low Stock')
                    ->query(fn ($query) => $query->whereRaw('current_stock <= min_stock_level')),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No ingredient inventories found')
            ->emptyStateDescription(
                'Create your first ingredient inventory to start managing stock levels and tracking supplies',
            )
            ->emptyStateActions([
                \Filament\Actions\Action::make('create')
                    ->label('Create Inventory')
                    ->icon('heroicon-o-plus')
                    ->url(route('filament.admin.resources.ingredient-inventories.create')),
            ])
            ->poll('30s'); // Refresh every 30 seconds for real-time updates
    }

    private static function getStockColor($record): string
    {
        if ($record->current_stock <= ($record->min_stock_level ?? 0)) {
            return 'danger';
        }

        if ($record->current_stock >= ($record->max_stock_level ?? PHP_FLOAT_MAX)) {
            return 'warning';
        }

        return 'success';
    }
}
