<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductIngredients\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

final class ProductIngredientInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('product.name')
                    ->label('Product'),
                TextEntry::make('ingredient.name')
                    ->label('Ingredient'),
                TextEntry::make('quantity_required')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
