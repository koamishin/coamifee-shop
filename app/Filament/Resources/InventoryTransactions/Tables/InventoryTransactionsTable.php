<?php

declare(strict_types=1);

namespace App\Filament\Resources\InventoryTransactions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class InventoryTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ingredient.name')
                    ->searchable(),
                TextColumn::make('transaction_type')
                    ->searchable(),
                TextColumn::make('quantity_change')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('previous_stock')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('new_stock')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('orderItem.id')
                    ->searchable(),
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
