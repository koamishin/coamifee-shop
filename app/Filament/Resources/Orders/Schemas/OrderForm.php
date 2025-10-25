<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('customer_name'),
                Select::make('customer_id')
                    ->relationship('customer', 'name'),
                TextInput::make('order_type')
                    ->required()
                    ->default('dine-in'),
                TextInput::make('payment_method')
                    ->required()
                    ->default('cash'),
                TextInput::make('total')
                    ->required()
                    ->numeric(),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                TextInput::make('table_number'),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
