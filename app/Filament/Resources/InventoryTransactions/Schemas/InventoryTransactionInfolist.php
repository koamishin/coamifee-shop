<?php

declare(strict_types=1);

namespace App\Filament\Resources\InventoryTransactions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

final class InventoryTransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('ingredient.name')
                    ->label('Ingredient'),
                TextEntry::make('transaction_type'),
                TextEntry::make('quantity_change')
                    ->numeric(),
                TextEntry::make('previous_stock')
                    ->numeric(),
                TextEntry::make('new_stock')
                    ->numeric(),
                TextEntry::make('reason')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('orderItem.id')
                    ->label('Order item')
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
