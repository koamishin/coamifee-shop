<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->description('Customer name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->limit(20),
                TextColumn::make('customer.name')
                    ->label('Account')
                    ->description('Registered customer account')
                    ->placeholder('Guest')
                    ->badge()
                    ->color(fn ($record) => $record->customer_id ? 'success' : 'gray')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('order_type')
                    ->label('Type')
                    ->description('Order type')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'dine-in' => 'success',
                        'takeout' => 'info',
                        'delivery' => 'warning',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable()
                    ->default('dine-in'),
                TextColumn::make('payment_method')
                    ->label('Payment')
                    ->description('Payment method')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'cash' => 'warning',
                        'card' => 'success',
                        'gcash' => 'primary',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total')
                    ->label('Total')
                    ->description('Order total amount')
                    ->money('USD')
                    ->sortable()
                    ->alignRight()
                    ->weight('bold'),
                IconColumn::make('status')
                    ->label('Status')
                    ->icon(fn ($state) => match ($state) {
                        'pending' => 'heroicon-o-clock',
                        'confirmed' => 'heroicon-o-check-circle',
                        'preparing' => 'heroicon-o-arrow-path',
                        'ready' => 'heroicon-o-bell',
                        'completed' => 'heroicon-o-check',
                        'cancelled' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'info',
                        'preparing' => 'primary',
                        'ready' => 'success',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('table_number')
                    ->label('Table')
                    ->description('Table number for dine-in orders')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable()
                    ->visible(fn ($record) => $record?->order_type === 'dine-in' && $record->table_number),
                TextColumn::make('items_count')
                    ->label('Items')
                    ->description('Number of items in order')
                    ->sortable()
                    ->alignCenter()
                    ->getStateUsing(fn ($record) => $record->items->count()),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->description('Date order was placed')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('order_type')
                    ->label('Order Type')
                    ->options([
                        'dine-in' => 'Dine-In',
                        'takeout' => 'Takeout',
                        'delivery' => 'Delivery',
                    ]),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'preparing' => 'Preparing',
                        'ready' => 'Ready',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                SelectFilter::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        'cash' => 'Cash',
                        'card' => 'Card',
                        'gcash' => 'GCash',
                    ]),
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make()
                    ->url(fn ($record) => route('filament.admin.resources.orders.view', $record)),
            ])
            ->bulkActions([
                \Filament\Actions\DeleteBulkAction::make(),
            ])
            ->emptyStateHeading('No orders found')
            ->emptyStateDescription('No orders match the current filters')
            ->poll('30s'); // Refresh every 30 seconds for real-time updates
    }
}
