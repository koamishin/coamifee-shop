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
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->size('sm')
                    ->prefix('#'),

                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->limit(25)
                    ->description(fn ($record) => $record->customer_id ? 'Registered Account' : 'Guest'),

                TextColumn::make('customer.name')
                    ->label('Account')
                    ->placeholder('Guest')
                    ->badge()
                    ->color(fn ($record): string => $record->customer_id ? 'success' : 'gray')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('order_type')
                    ->label('Type')
                    ->badge()
                    ->icon(fn ($state): string => match ($state) {
                        'dine_in', 'dine-in' => 'heroicon-o-building-storefront',
                        'takeaway' => 'heroicon-o-shopping-bag',
                        'delivery' => 'heroicon-o-truck',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn ($state): string => match ($state) {
                        'dine_in', 'dine-in' => 'success',
                        'takeaway' => 'info',
                        'delivery' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state): string => match ($state) {
                        'dine_in', 'dine-in' => 'Dine In',
                        'takeaway' => 'Takeaway',
                        'delivery' => 'Delivery',
                        default => ucfirst(str_replace('_', ' ', (string) $state)),
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('table_number')
                    ->label('Table')
                    ->badge()
                    ->icon('heroicon-o-building-office-2')
                    ->color('primary')
                    ->placeholder('-')
                    ->formatStateUsing(fn ($state): string => $state ? str_replace('_', ' ', ucfirst($state)) : '-')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('items_count')
                    ->label('Items')
                    ->badge()
                    ->color('info')
                    ->alignCenter()
                    ->counts('items')
                    ->suffix(' items'),

                TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money(self::getMoneyConfig())
                    ->sortable()
                    ->alignRight()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('discount_amount')
                    ->label('Discount')
                    ->money(self::getMoneyConfig())
                    ->sortable()
                    ->alignRight()
                    ->placeholder('-')
                    ->color('danger')
                    ->icon('heroicon-o-tag')
                    ->description(fn ($record) => $record->discount_amount > 0 && $record->discount_type && $record->discount_value
                        ? ucfirst($record->discount_type).' ('.$record->discount_value.'%)'
                        : null)
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('add_ons_total')
                    ->label('Add-ons')
                    ->money(self::getMoneyConfig())
                    ->sortable()
                    ->alignRight()
                    ->placeholder('-')
                    ->color('success')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total')
                    ->label('Total')
                    ->money(self::getMoneyConfig())
                    ->sortable()
                    ->alignRight()
                    ->weight('bold')
                    ->size('lg')
                    ->color('success'),

                TextColumn::make('payment_method')
                    ->label('Payment')
                    ->badge()
                    ->icon(fn ($state): string => app(\App\Services\GeneralSettingsService::class)->getPaymentMethodIcon((string) $state))
                    ->color(fn ($state): string => app(\App\Services\GeneralSettingsService::class)->getPaymentMethodColor((string) $state))
                    ->formatStateUsing(fn ($state): string => app(\App\Services\GeneralSettingsService::class)->getPaymentMethodDisplayName((string) $state))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('payment_status')
                    ->label('Payment Status')
                    ->badge()
                    ->icon(fn ($state): string => match ($state) {
                        'paid' => 'heroicon-o-check-circle',
                        'pending' => 'heroicon-o-clock',
                        'failed' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn ($state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state): string => ucfirst((string) $state))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->icon(fn ($state): string => match ($state) {
                        'pending' => 'heroicon-o-clock',
                        'confirmed' => 'heroicon-o-check-circle',
                        'preparing' => 'heroicon-o-arrow-path',
                        'ready' => 'heroicon-o-bell-alert',
                        'served' => 'heroicon-o-check-badge',
                        'completed' => 'heroicon-o-check',
                        'cancelled' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn ($state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'info',
                        'preparing' => 'primary',
                        'ready' => 'success',
                        'served' => 'success',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state): string => ucfirst((string) $state))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Order Date')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->description(fn ($record) => $record->created_at->diffForHumans()),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('order_type')
                    ->label('Order Type')
                    ->options([
                        'dine_in' => 'Dine In',
                        'dine-in' => 'Dine In (Legacy)',
                        'takeaway' => 'Takeaway',
                        'delivery' => 'Delivery',
                    ]),
                SelectFilter::make('status')
                    ->label('Order Status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'preparing' => 'Preparing',
                        'ready' => 'Ready',
                        'served' => 'Served',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->multiple(),
                SelectFilter::make('payment_method')
                    ->label('Payment Method')
                    ->options(function () {
                        $settingsService = app(\App\Services\GeneralSettingsService::class);
                        $enabledMethods = $settingsService->getEnabledPaymentMethods();
                        $options = [];

                        foreach ($enabledMethods as $method => $config) {
                            $options[$method] = $config['name'];
                        }

                        return $options;
                    })
                    ->multiple(),
                SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        'paid' => 'Paid',
                        'pending' => 'Pending',
                        'failed' => 'Failed',
                    ])
                    ->multiple(),
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
