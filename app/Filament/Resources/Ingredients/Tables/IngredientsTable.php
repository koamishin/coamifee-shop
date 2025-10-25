<?php

declare(strict_types=1);

namespace App\Filament\Resources\Ingredients\Tables;

use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class IngredientsTable
{
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
                    ->color(fn ($state) => match ($state) {
                        'grams' => 'warning',
                        'ml' => 'info',
                        'pieces' => 'success',
                        'liters' => 'primary',
                        'kilograms' => 'danger',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_trackable')
                    ->label('Tracking')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                TextColumn::make('current_stock')
                    ->label('Stock')
                    ->description('Current stock level')
                    ->numeric(decimalPlaces: 2, thousandsSeparator: ',')
                    ->sortable()
                    ->alignRight()
                    ->weight('medium')
                    ->color(fn ($record) => self::getStockColor($record)),
                TextColumn::make('unit_cost')
                    ->label('Unit Cost')
                    ->description('Cost per unit')
                    ->money('USD')
                    ->sortable()
                    ->alignRight(),
                TextColumn::make('supplier')
                    ->label('Supplier')
                    ->description('Primary supplier')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                TextColumn::make('created_at')
                    ->label('Added')
                    ->description('Date ingredient was added')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('unit_type')
                    ->label('Unit Type')
                    ->options([
                        'grams' => 'Grams',
                        'ml' => 'Milliliters',
                        'pieces' => 'Pieces',
                        'liters' => 'Liters',
                        'kilograms' => 'Kilograms',
                    ]),
                SelectFilter::make('is_trackable')
                    ->label('Tracking')
                    ->options([
                        '1' => 'Trackable',
                        '0' => 'Not Trackable',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('View Inventory')
                    ->label('Inventory')
                    ->icon('heroicon-o-cube')
                    ->url(fn ($record) => $record->is_trackable && $record->inventory ? route('filament.admin.resources.ingredient-inventories.index', ['ingredientId' => $record->id]) : null)
                    ->hidden(fn ($record) => ! $record->is_trackable || ! $record->inventory)
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->emptyStateHeading('No ingredients found')
            ->emptyStateDescription('Create your first ingredient to get started with inventory management')
            ->emptyStateActions([
                \Filament\Actions\CreateAction::make()
                    ->label('Create Ingredient')
                    ->url(route('filament.admin.resources.ingredients.create')),
            ])
            ->poll('60s'); // Refresh every minute for real-time updates
    }

    private static function getStockColor($record): string
    {
        if (! $record->is_trackable) {
            return 'gray';
        }

        $inventory = $record->inventory()->first();
        if (! $inventory) {
            return 'danger';
        }

        if ($inventory->current_stock <= ($inventory->min_stock_level ?? 0)) {
            return 'danger';
        }

        if ($inventory->current_stock >= ($inventory->max_stock_level ?? PHP_FLOAT_MAX)) {
            return 'warning';
        }

        return 'success';
    }
}
