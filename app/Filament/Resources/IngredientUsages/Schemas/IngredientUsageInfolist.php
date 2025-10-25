<?php

declare(strict_types=1);

namespace App\Filament\Resources\IngredientUsages\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

final class IngredientUsageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('orderItem.id')
                    ->label('Order item'),
                TextEntry::make('ingredient.name')
                    ->label('Ingredient'),
                TextEntry::make('quantity_used')
                    ->numeric(),
                TextEntry::make('recorded_at')
                    ->dateTime(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
