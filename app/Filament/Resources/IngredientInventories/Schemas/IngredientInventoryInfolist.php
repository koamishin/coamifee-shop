<?php

declare(strict_types=1);

namespace App\Filament\Resources\IngredientInventories\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

final class IngredientInventoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('ingredient.name')
                    ->label('Ingredient'),
                TextEntry::make('current_stock')
                    ->numeric(),
                TextEntry::make('min_stock_level')
                    ->numeric(),
                TextEntry::make('max_stock_level')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('location')
                    ->placeholder('-'),
                TextEntry::make('last_restocked_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
