<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Product Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->description('The name of the product')
                    ->color('primary'),

                TextColumn::make('price')
                    ->label('Price')
                    ->money('USD')
                    ->sortable()
                    ->alignCenter()
                    ->description('Current selling price')
                    ->color('success'),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->description('Product category')
                    ->alignCenter(),

                TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color(
                        fn ($record) => $record->stock_quantity > 10
                            ? 'success'
                            : ($record->stock_quantity > 5
                                ? 'warning'
                                : 'danger'),
                    )
                    ->description('Available stock quantity')
                    ->formatStateUsing(fn ($state) => $state.' units'),

                ImageColumn::make('image_url')
                    ->label('Image')
                    ->size(60)
                    ->circular()
                    ->defaultImageUrl(url('/placeholder-product.png'))

                    ->alignCenter(),

                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->alignCenter(),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('SKU copied to clipboard')
                    ->description('Stock Keeping Unit')
                    ->size('sm'),

                TextColumn::make('preparation_time')
                    ->label('Prep Time')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->suffix(' min')
                    ->description('Average preparation time in minutes')
                    ->color('warning')
                    ->badge()
                    ->color('info'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->description('When this product was added')
                    ->color('gray'),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->description('Last time this product was modified')
                    ->color('gray'),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->placeholder('All categories')
                    ->native(false),

                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ])
                    ->native(false),

                Filter::make('in_stock')
                    ->label('In Stock')
                    ->query(
                        fn (Builder $query): Builder => $query->where(
                            'stock_quantity',
                            '>',
                            0,
                        ),
                    )
                    ->toggle(),

                Filter::make('low_stock')
                    ->label('Low Stock')
                    ->query(
                        fn (Builder $query): Builder => $query->where(
                            'stock_quantity',
                            '<=',
                            5,
                        ),
                    )
                    ->toggle(),
            ])
            ->actions([
                ViewAction::make()->color('primary')->icon('heroicon-o-eye'),

                EditAction::make()->color('warning')->icon('heroicon-o-pencil'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Products')
                        ->modalDescription(
                            'Are you sure you want to delete these products? This action cannot be undone.',
                        )
                        ->modalSubmitActionLabel('Yes, delete them'),
                ]),
            ])
            ->emptyStateHeading('No products found')
            ->emptyStateDescription('Create your first product to get started.')
            ->emptyStateActions([
                \Filament\Actions\CreateAction::make()
                    ->label('Create Product')
                    ->icon('heroicon-o-plus')
                    ->url(route('filament.admin.resources.products.create')),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
