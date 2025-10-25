<?php

declare(strict_types=1);

namespace App\Filament\Resources\IngredientInventories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class IngredientInventoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ingredient.name')
                    ->searchable(),
                TextColumn::make('current_stock')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('min_stock_level')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('max_stock_level')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('location')
                    ->searchable(),
                TextColumn::make('last_restocked_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
