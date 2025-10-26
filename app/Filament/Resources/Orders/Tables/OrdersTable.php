<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Tables;

use App\Filament\Concerns\CurrencyAware;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class OrdersTable
{
    use CurrencyAware;

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Order #')
                    ->description('Order identification number')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->size('sm'),

                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->description('Customer name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->limit(25),

                TextColumn::make('customer.name')
                    ->label('Account')
                    ->description('Registered customer account')
                    ->placeholder('Guest Customer')
                    ->badge()
                    ->color(
                        fn ($record): string => $record->customer_id
                            ? 'success'
                            : 'gray',
                    )
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(
                        fn ($state): string => $state ? 'Registered' : 'Guest',
                    ),

                TextColumn::make('order_type')
                    ->label('Type')
                    ->description('Order fulfillment type')
                    ->badge()
                    ->icon(
                        fn ($state): string => match ($state) {
                            'dine-in' => 'heroicon-o-restaurant',
                            'takeaway' => 'heroicon-o-briefcase',
                            'delivery' => 'heroicon-o-truck',
                            default => 'heroicon-o-question-mark-circle',
                        },
                    )
                    ->color(
                        fn ($state): string => match ($state) {
                            'dine-in' => 'success',
                            'takeaway' => 'info',
                            'delivery' => 'warning',
                            default => 'gray',
                        },
                    )
                    ->formatStateUsing(
                        fn ($state): string => match ($state) {
                            'dine-in' => 'Dine In',
                            'takeaway' => 'Takeaway',
                            'delivery' => 'Delivery',
                            default => ucfirst((string) $state),
                        },
                    )
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->description('Current order status')
                    ->badge()
                    ->icon(
                        fn ($state): string => match ($state) {
                            'pending' => 'heroicon-o-clock',
                            'confirmed' => 'heroicon-o-check-circle',
                            'preparing' => 'heroicon-o-arrow-path',
                            'ready' => 'heroicon-o-bell',
                            'served' => 'heroicon-o-restaurant',
                            'completed' => 'heroicon-o-check',
                            'cancelled' => 'heroicon-o-x-circle',
                            default => 'heroicon-o-question-mark-circle',
                        },
                    )
                    ->color(
                        fn ($state): string => match ($state) {
                            'pending' => 'warning',
                            'confirmed' => 'info',
                            'preparing' => 'primary',
                            'ready' => 'success',
                            'served' => 'success',
                            'completed' => 'success',
                            'cancelled' => 'danger',
                            default => 'gray',
                        },
                    )
                    ->formatStateUsing(
                        fn ($state): string => match ($state) {
                            'pending' => 'Pending',
                            'confirmed' => 'Confirmed',
                            'preparing' => 'Preparing',
                            'ready' => 'Ready',
                            'served' => 'Served',
                            'completed' => 'Completed',
                            'cancelled' => 'Cancelled',
                            default => ucfirst((string) $state),
                        },
                    )
                    ->searchable()
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->label('Payment')
                    ->description('Payment method used')
                    ->badge()
                    ->icon(
                        fn ($state): string => match ($state) {
                            'cash' => 'heroicon-o-banknotes',
                            'card' => 'heroicon-o-credit-card',
                            'digital' => 'heroicon-o-device-phone-mobile',
                            'bank_transfer' => 'heroicon-o-building-office',
                            default => 'heroicon-o-question-mark-circle',
                        },
                    )
                    ->color(
                        fn ($state): string => match ($state) {
                            'cash' => 'warning',
                            'card' => 'success',
                            'digital' => 'primary',
                            'bank_transfer' => 'info',
                            default => 'gray',
                        },
                    )
                    ->formatStateUsing(
                        fn ($state): string => match ($state) {
                            'cash' => 'Cash',
                            'card' => 'Card',
                            'digital' => 'Digital',
                            'bank_transfer' => 'Bank Transfer',
                            default => ucfirst(str_replace('_', ' ', $state)),
                        },
                    )
                    ->searchable()
                    ->sortable(),

                TextColumn::make('table_number')
                    ->label('Table')
                    ->description('Table number for dine-in')
                    ->badge()
                    ->icon('heroicon-o-building-office-2')
                    ->color('gray')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable()
                    ->visible(
                        fn ($record): bool => $record?->order_type === 'dine-in',
                    ),

                TextColumn::make('total')
                    ->label('Total')
                    ->description('Order total amount')
                    ->money(self::getMoneyConfig())
                    ->sortable()
                    ->alignRight()
                    ->weight('bold')
                    ->size('lg'),

                TextColumn::make('items_count')
                    ->label('Items')
                    ->description('Number of items')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->alignCenter()
                    ->getStateUsing(fn ($record) => $record->items->count()),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->description('Order placed date')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->description('Last updated')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('order_type')
                    ->label('Order Type')
                    ->options([
                        'dine-in' => '🍽️ Dine In',
                        'takeaway' => '🥤 Takeaway',
                        'delivery' => '🚚 Delivery',
                    ]),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => '⏰ Pending',
                        'confirmed' => '✅ Confirmed',
                        'preparing' => '🔄 Preparing',
                        'ready' => '🔔 Ready',
                        'served' => '🍽️ Served',
                        'completed' => '✅ Completed',
                        'cancelled' => '❌ Cancelled',
                    ]),
                SelectFilter::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        'cash' => '💵 Cash',
                        'card' => '💳 Card',
                        'digital' => '📱 Digital Wallet',
                        'bank_transfer' => '🏦 Bank Transfer',
                    ]),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('View Details')
                        ->icon('heroicon-o-eye'),
                    EditAction::make()
                        ->label('Edit Order')
                        ->icon('heroicon-o-pencil'),
                    Action::make('update_status')
                        ->label('Update Status')
                        ->icon('heroicon-o-arrow-path')
                        ->form([
                            Select::make('status')
                                ->label('New Status')
                                ->options([
                                    'pending' => 'Pending',
                                    'confirmed' => 'Confirmed',
                                    'preparing' => 'Preparing',
                                    'ready' => 'Ready',
                                    'served' => 'Served',
                                    'completed' => 'Completed',
                                    'cancelled' => 'Cancelled',
                                ])
                                ->required(),
                        ])
                        ->action(function (array $data, $record): void {
                            $record->update(['status' => $data['status']]);
                        })
                        ->visible(
                            fn ($record): bool => ! in_array($record->status, [
                                'completed',
                                'cancelled',
                            ]),
                        ),
                    DeleteAction::make()
                        ->label('Delete Order')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->modalHeading('Delete Order')
                        ->modalDescription(
                            'Are you sure you want to delete this order? This action cannot be undone.',
                        )
                        ->modalSubmitActionLabel('Yes, delete it'),
                ])
                    ->label('Actions')
                    ->icon('heroicon-o-ellipsis-horizontal')
                    ->color('primary'),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Delete Selected Orders')
                    ->modalDescription(
                        'Are you sure you want to delete the selected orders? This action cannot be undone.',
                    )
                    ->modalSubmitActionLabel('Yes, delete them'),
            ])
            ->emptyStateHeading('No orders found')
            ->emptyStateDescription(
                'Create your first order or adjust your filters to see results',
            )
            ->emptyStateActions([
                CreateAction::make()
                    ->label('Create Order')
                    ->icon('heroicon-o-plus'),
            ])
            ->poll('30s') // Refresh every 30 seconds for real-time updates
            ->striped(); // Add striped rows for better readability
    }
}
