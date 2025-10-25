<?php

declare(strict_types=1);

namespace App\Filament\Resources\IngredientUsages\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class IngredientUsageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('order_item_id')
                    ->relationship('orderItem', 'id')
                    ->required(),
                Select::make('ingredient_id')
                    ->relationship('ingredient', 'name')
                    ->required(),
                TextInput::make('quantity_used')
                    ->required()
                    ->numeric(),
                DateTimePicker::make('recorded_at')
                    ->required(),
            ]);
    }
}
