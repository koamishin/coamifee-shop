<?php

declare(strict_types=1);

namespace App\Filament\Resources\IngredientInventories\Schemas;

use App\Filament\Concerns\CurrencyAware;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class IngredientInventoryInfolist
{
    use CurrencyAware;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ingredient Information')
                    ->description('Basic ingredient details')
                    ->icon('heroicon-o-cube')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('ingredient.name')
                                ->label('Ingredient Name')
                                ->size('text-lg')
                                ->weight('bold'),
                            TextEntry::make('ingredient.unit_type')
                                ->label('Unit of Measurement')
                                ->badge()
                                ->color(fn ($state) => $state?->getColor() ?? 'gray')
                                ->icon(fn ($state) => $state?->getIcon())
                                ->formatStateUsing(fn ($state) => $state?->getLabel()),
                        ]),
                    ]),

                Section::make('Stock Levels')
                    ->description('Current inventory status and stock levels')
                    ->icon('heroicon-o-archive-box')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('current_stock')
                                ->label('Current Stock')
                                ->numeric(decimalPlaces: 3, thousandsSeparator: ',')
                                ->icon('heroicon-o-cube')
                                ->color(self::getStockColor(...)),
                            TextEntry::make('reorder_level')
                                ->label('Reorder Level')
                                ->numeric(decimalPlaces: 3, thousandsSeparator: ',')
                                ->icon('heroicon-o-bell-alert')
                                ->color('warning'),
                        ]),
                        Grid::make(2)->schema([
                            TextEntry::make('min_stock_level')
                                ->label('Minimum Stock')
                                ->numeric(decimalPlaces: 3, thousandsSeparator: ',')
                                ->icon('heroicon-o-arrow-down')
                                ->color('danger'),
                            TextEntry::make('max_stock_level')
                                ->label('Maximum Stock')
                                ->numeric(decimalPlaces: 3, thousandsSeparator: ',')
                                ->icon('heroicon-o-arrow-up')
                                ->color('success')
                                ->placeholder('Not set'),
                        ]),
                    ]),

                Section::make('Storage & Supplier')
                    ->description('Storage location and supplier information')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        TextEntry::make('location')
                            ->label('Storage Location')
                            ->placeholder('Not specified')
                            ->icon('heroicon-o-map-pin'),
                        TextEntry::make('supplier_info')
                            ->label('Supplier Information')
                            ->placeholder('Not specified')
                            ->icon('heroicon-o-building-office')
                            ->columnSpanFull(),
                    ]),

                Section::make('System Information')
                    ->description('Timestamps and system data')
                    ->icon('heroicon-o-cog')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('last_restocked_at')
                                ->label('Last Restocked')
                                ->dateTime('M j, Y g:i A')
                                ->placeholder('Never')
                                ->icon('heroicon-o-arrow-path'),
                            TextEntry::make('created_at')
                                ->label('Created')
                                ->dateTime('M j, Y g:i A')
                                ->icon('heroicon-o-calendar'),
                        ]),
                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime('M j, Y g:i A')
                            ->icon('heroicon-o-clock')
                            ->columnSpan(2),
                    ]),
            ]);
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
