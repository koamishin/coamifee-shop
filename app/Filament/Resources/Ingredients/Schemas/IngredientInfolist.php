<?php

declare(strict_types=1);

namespace App\Filament\Resources\Ingredients\Schemas;

use App\Filament\Concerns\CurrencyAware;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class IngredientInfolist
{
    use CurrencyAware;

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Basic Information')
                ->description('Ingredient details and specifications')
                ->icon('heroicon-o-cube')
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('name')
                            ->label('Ingredient Name')
                            ->size('text-lg')
                            ->weight('bold'),
                        TextEntry::make('unit_type')
                            ->label('Unit')
                            ->badge()
                            ->color(
                                fn ($state) => match ($state) {
                                    'grams' => 'warning',
                                    'ml' => 'info',
                                    'pieces' => 'success',
                                    'liters' => 'primary',
                                    'kilograms' => 'danger',
                                    default => 'gray',
                                },
                            ),
                    ]),
                    TextEntry::make('description')
                        ->label('Description')
                        ->placeholder('No description provided')
                        ->columnSpanFull(),
                ]),

            Section::make('Inventory & Cost')
                ->description('Stock levels and pricing information')
                ->icon('heroicon-o-currency-dollar')
                ->visible(fn ($record) => $record->is_trackable)
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('current_stock')
                            ->label('Current Stock')

                            ->numeric(decimalPlaces: 2, thousandsSeparator: ',')
                            ->icon('heroicon-o-cube')
                            ->color(
                                fn ($record) => self::getStockColor($record),
                            ),
                        TextEntry::make('unit_cost')
                            ->label('Unit Cost')
                            ->money(self::getMoneyConfig())
                            ->icon('heroicon-o-tag'),
                    ]),
                    TextEntry::make('inventory.min_stock_level')
                        ->label('Minimum Stock')
                        ->numeric(decimalPlaces: 2)
                        ->placeholder('Not set'),
                    TextEntry::make('inventory.max_stock_level')
                        ->label('Maximum Stock')
                        ->numeric(decimalPlaces: 2)
                        ->placeholder('Not set'),
                ]),

            Section::make('Supplier Information')
                ->description('Supplier and procurement details')
                ->icon('heroicon-o-truck')
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('supplier')
                            ->label('Primary Supplier')
                            ->placeholder('Not specified'),
                        IconEntry::make('is_trackable')
                            ->label('Stock Tracking')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger'),
                    ]),
                    TextEntry::make('inventory.location')
                        ->label('Storage Location')
                        ->placeholder('Not specified')
                        ->visible(fn ($record) => $record->is_trackable),
                ]),

            Section::make('System Information')
                ->description('Timestamps and system data')
                ->icon('heroicon-o-cog')
                ->collapsible()
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime('M j, Y g:i A')
                            ->icon('heroicon-o-calendar'),
                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime('M j, Y g:i A')
                            ->icon('heroicon-o-arrow-path'),
                    ]),
                ]),
        ]);
    }

    private static function getStockColor($record): string
    {
        if (! $record->is_trackable) {
            return 'gray';
        }

        $inventory = $record->inventory;
        if (! $inventory) {
            return 'danger';
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
