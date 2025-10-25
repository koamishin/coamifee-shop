<?php

declare(strict_types=1);

namespace App\Filament\Resources\IngredientInventories\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

final class IngredientInventoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Inventory Settings')
                    ->description('Configure stock levels and tracking for this ingredient')
                    ->icon('heroicon-o-cube')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('ingredient_id')
                                    ->label('Ingredient')
                                    ->relationship('ingredient', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Select an ingredient')
                                    ->helperText('Choose the ingredient to manage inventory for')
                                    ->columnSpan(2),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('current_stock')
                                    ->label('Current Stock')
                                    ->required()
                                    ->numeric()
                                    ->step(0.01)
                                    ->placeholder('e.g., 5000')
                                    ->helperText('Current available quantity')
                                    ->prefixIcon('heroicon-o-archive-box')
                                    ->columnSpan(1),
                                TextInput::make('min_stock_level')
                                    ->label('Minimum Stock')
                                    ->required()
                                    ->numeric()
                                    ->step(0.01)
                                    ->default(0)
                                    ->placeholder('e.g., 100')
                                    ->helperText('Alert when stock falls below this level')
                                    ->prefixIcon('heroicon-o-arrow-down')
                                    ->columnSpan(1),
                                TextInput::make('max_stock_level')
                                    ->label('Maximum Stock')
                                    ->numeric()
                                    ->step(0.01)
                                    ->placeholder('e.g., 10000')
                                    ->helperText('Alert when stock exceeds this level')
                                    ->prefixIcon('heroicon-o-arrow-up')
                                    ->columnSpan(1),
                            ]),

                        Grid::make(1)
                            ->schema([
                                TextInput::make('location')
                                    ->label('Storage Location')
                                    ->placeholder('e.g., Main Storage, Fridge, Freezer')
                                    ->helperText('Physical location where this ingredient is stored')
                                    ->prefixIcon('heroicon-o-map-pin')
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Section::make('Restock Information')
                    ->description('Track when inventory was last replenished')
                    ->icon('heroicon-o-truck')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                DateTimePicker::make('last_restocked_at')
                                    ->label('Last Restocked')
                                    ->placeholder('Select date and time')
                                    ->helperText('When this ingredient was last restocked')
                                    ->prefixIcon('heroicon-o-calendar')
                                    ->displayFormat('M j, Y g:i A')
                                    ->columnSpan(1),
                                Placeholder::make('stock_status')
                                    ->label('Stock Status')
                                    ->content(function ($get, $record) {
                                        if (! $record) {
                                            return '⚪ New Inventory';
                                        }

                                        $current = (float) $get('current_stock');
                                        $min = (float) $get('min_stock_level');
                                        $max = (float) $get('max_stock_level');

                                        if ($current <= $min) {
                                            return new HtmlString('🔴 <span style="color: #dc2626;">Low Stock</span>');
                                        }

                                        if ($max && $current >= $max) {
                                            return new HtmlString('🟠 <span style="color: #f59e0b;">Overstocked</span>');
                                        }

                                        return new HtmlString('🟢 <span style="color: #16a34a;">Normal</span>');
                                    })
                                    ->columnSpan(1),
                            ]),
                    ]),

                Section::make('Quick Actions')
                    ->description('Common inventory management actions')
                    ->icon('heroicon-o-lightning-bolt')
                    ->schema([
                        ToggleButtons::make('quick_action')
                            ->label('Quick Action')
                            ->grouped()
                            ->inline()
                            ->options([
                                'restock' => 'Restock +100',
                                'adjust' => 'Set to Min',
                                'waste' => 'Record Waste -50',
                            ])
                            ->action(function (array $state) use ($schema) {
                                $record = $schema->getModel();
                                $current = (float) $record->current_stock;

                                match ($state) {
                                    'restock' => $record->current_stock = $current + 100,
                                    'adjust' => $record->current_stock = (float) $record->min_stock_level,
                                    'waste' => $record->current_stock = max(0, $current - 50),
                                    default => null,
                                };

                                $record->save();
                            })
                            ->columnSpanFull(),
                    ]),
            ])
            ->columns(1);
    }
}
