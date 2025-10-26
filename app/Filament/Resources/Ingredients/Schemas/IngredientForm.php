<?php

declare(strict_types=1);

namespace App\Filament\Resources\Ingredients\Schemas;

use App\Filament\Concerns\CurrencyAware;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class IngredientForm
{
    use CurrencyAware;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->description('Manage ingredient details and settings')
                    ->icon('heroicon-o-cube')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('Ingredient Name')
                                ->required()
                                ->maxLength(100)
                                ->placeholder('e.g., Arabica Coffee Beans')
                                ->helperText(
                                    'Enter the full name of the ingredient',
                                )
                                ->columnSpan(1),
                            TextInput::make('unit_type')
                                ->label('Unit of Measurement')
                                ->required()
                                ->placeholder('e.g., grams, ml, pieces')
                                ->helperText(
                                    'How this ingredient is measured and tracked',
                                )
                                ->columnSpan(1),
                        ]),
                        TextInput::make('supplier')
                            ->label('Supplier')
                            ->placeholder('e.g., Local Coffee Roasters')
                            ->helperText('Primary supplier for this ingredient')
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->label('Description')
                            ->placeholder(
                                'e.g., Premium Arabica beans from Colombia, medium roast',
                            )
                            ->helperText(
                                'Detailed description of the ingredient',
                            )
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Inventory & Cost Settings')
                    ->description('Configure tracking and cost management')
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        Grid::make(2)->schema([
                            Toggle::make('is_trackable')
                                ->label('Track Stock Levels')
                                ->helperText(
                                    'Enable real-time stock tracking for this ingredient',
                                )
                                ->default(true)
                                ->reactive()
                                ->afterStateUpdated(
                                    fn (
                                        $state,
                                        callable $set,
                                    ) => self::updateStockFieldsVisibility(
                                        $state,
                                        $set,
                                    ),
                                )
                                ->columnSpan(1),
                            TextInput::make('current_stock')
                                ->label('Current Stock Level')
                                ->numeric()
                                ->step(0.01)
                                ->placeholder('e.g., 5000')
                                ->helperText('Current available quantity')
                                ->columnSpan(1),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('unit_cost')
                                ->label('Cost Per Unit')
                                ->prefix(self::getCurrencyPrefix())
                                ->suffix(self::getCurrencySuffix())
                                ->numeric()
                                ->step(0.001)
                                ->placeholder('e.g., 0.020')
                                ->helperText('Cost per unit of measurement')
                                ->columnSpan(1),
                            Placeholder::make('stock_status')
                                ->label('Stock Status')
                                ->content(
                                    fn ($record) => self::getStockStatus(
                                        $record,
                                    ),
                                )
                                ->columnSpan(1),
                        ]),
                    ]),
            ])
            ->columns(1);
    }

    private static function updateStockFieldsVisibility(
        bool $isTrackable,
        callable $set,
    ): void {
        $set('current_stock', $isTrackable);
    }

    private static function getStockStatus(?Ingredient $record): string
    {
        if (! $record || ! $record->is_trackable) {
            return '⚪ Not Tracked';
        }

        if (! $record->id) {
            return '⚪ New Ingredient';
        }

        $inventory = $record->inventory()->first();
        if (! $inventory) {
            return '🔴 No Inventory Set';
        }

        if ($inventory->current_stock <= ($inventory->min_stock_level ?? 0)) {
            return '🔴 Low Stock';
        }

        if (
            $inventory->current_stock >=
            ($inventory->max_stock_level ?? PHP_FLOAT_MAX)
        ) {
            return '🟠 Overstocked';
        }

        return '🟢 Normal';
    }
}
