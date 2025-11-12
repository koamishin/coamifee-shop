<?php

declare(strict_types=1);

namespace App\Filament\Resources\Ingredients\Tables;

use App\Enums\UnitType;
use App\Filament\Concerns\CurrencyAware;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class IngredientsTable
{
    use CurrencyAware;

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('name')
                    ->label('Ingredient')
                    ->description('Name of the ingredient')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('unit_type')
                    ->label('Unit')
                    ->description('Measurement unit')
                    ->badge()
                    ->color(fn ($state) => $state?->getColor() ?? 'gray')
                    ->icon(fn ($state) => $state?->getIcon())
                    ->formatStateUsing(fn ($state) => $state?->getLabel())
                    ->searchable()
                    ->sortable(),
                TextColumn::make('inventory.current_stock')
                    ->label('Stock')
                    ->description('Current stock level')
                    ->numeric(decimalPlaces: 1, thousandsSeparator: ',')
                    ->sortable()
                    ->alignRight()
                    ->weight('medium')
                    ->color(self::getStockColor(...))
                    ->placeholder('No inventory'),
                TextColumn::make('created_at')
                    ->label('Added')
                    ->description('Date ingredient was added')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('unit_type')
                    ->label('Unit Type')
                    ->options(UnitType::getOptions()),

            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('Manage Inventory')
                    ->label('Manage Inventory')
                    ->icon('heroicon-o-archive-box')
                    ->url(
                        fn ($record): string => $record->inventory
                            ? route('filament.admin.resources.ingredient-inventories.edit', $record->inventory)
                            : route('filament.admin.resources.ingredient-inventories.create', ['ingredient_id' => $record->id]),
                    )
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([DeleteBulkAction::make()])
            ->emptyStateHeading('No ingredients found')
            ->emptyStateDescription(
                'Create your first ingredient or go directly to inventory management to add ingredients and their stock information',
            )
            ->emptyStateActions([
                CreateAction::make()
                    ->label('Create Ingredient')
                    ->url(route('filament.admin.resources.ingredients.create')),
                Action::make('manage_inventory')
                    ->label('Manage Inventory')
                    ->icon('heroicon-o-archive-box')
                    ->url(route('filament.admin.resources.ingredient-inventories.create')),
            ]);
    }

    private static function getStockColor($record): string
    {
        $inventory = $record->inventory;
        if (! $inventory) {
            return 'gray';
        }

        if ($inventory->current_stock <= ($inventory->min_stock_level ?? 0)) {
            return 'danger';
        }

        if (
            $inventory->current_stock >=
            ($inventory->max_stock_level ?? PHP_FLOAT_MAX)
        ) {
            return 'warning';
        }

        return 'success';
    }
}
