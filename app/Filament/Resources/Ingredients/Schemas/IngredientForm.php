<?php

declare(strict_types=1);

namespace App\Filament\Resources\Ingredients\Schemas;

use App\Enums\UnitType;
use App\Filament\Concerns\CurrencyAware;
use App\Models\Ingredient;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
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
                    ->description('Manage ingredient basic details')
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
                            Select::make('unit_type')
                                ->label('Unit of Measurement')
                                ->required()
                                ->options(UnitType::getOptions())
                                ->searchable()
                                ->preload()
                                ->placeholder('Select unit type')
                                ->helperText(
                                    'How this ingredient is measured and tracked',
                                )
                                ->columnSpan(1),
                        ]),
                    ]),
            ])
            ->columns(1);
    }


}
