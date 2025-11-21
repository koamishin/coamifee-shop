<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('phone')
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
                EditAction::make(),
                Action::make('view_orders')
                    ->label('View Orders')
                    ->icon(Heroicon::Eye)
                    ->color('gray')
                    ->modalHeading(fn ($record) => "Order History for {$record->name}")
                    ->modalContent(function ($record) {
                        $orders = $record->orders()
                            ->with(['items.product'])
                            ->latest()
                            ->get();

                        if ($orders->isEmpty()) {
                            return view('filament.resources.customers.empty-orders');
                        }

                        return view('filament.resources.customers.order-history', [
                            'orders' => $orders,
                            'customer' => $record,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
