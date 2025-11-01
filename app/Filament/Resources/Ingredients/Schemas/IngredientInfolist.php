<?php

declare(strict_types=1);

namespace App\Filament\Resources\Ingredients\Schemas;

use App\Filament\Concerns\CurrencyAware;
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
                            ->color(fn ($state) => $state?->getColor() ?? 'gray')
                            ->icon(fn ($state) => $state?->getIcon())
                            ->formatStateUsing(fn ($state) => $state?->getLabel()),
                    ]),
                ]),

            Section::make('Related Inventory')
                ->description('Inventory and stock information (if available)')
                ->icon('heroicon-o-archive-box')
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('inventory.current_stock')
                            ->label('Current Stock')
                            ->numeric(decimalPlaces: 2, thousandsSeparator: ',')
                            ->icon('heroicon-o-cube')
                            ->color(
                                self::getStockColor(...),
                            )
                            ->placeholder('No inventory set'),
                        TextEntry::make('inventory.unit_cost')
                            ->label('Unit Cost')
                            ->money(self::getMoneyConfig())
                            ->icon('heroicon-o-tag')
                            ->placeholder('Not set'),
                    ]),
                    TextEntry::make('inventory.location')
                        ->label('Storage Location')
                        ->placeholder('Not specified'),
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
        $inventory = $record->inventory;
        if (! $inventory) {
            return 'gray';
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
