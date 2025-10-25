<?php

declare(strict_types=1);

namespace App\Filament\Resources\InventoryTransactions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class InventoryTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('ingredient_id')
                    ->relationship('ingredient', 'name')
                    ->required(),
                TextInput::make('transaction_type')
                    ->required(),
                TextInput::make('quantity_change')
                    ->required()
                    ->numeric(),
                TextInput::make('previous_stock')
                    ->required()
                    ->numeric(),
                TextInput::make('new_stock')
                    ->required()
                    ->numeric(),
                Textarea::make('reason')
                    ->columnSpanFull(),
                Select::make('order_item_id')
                    ->relationship('orderItem', 'id'),
            ]);
    }
}
