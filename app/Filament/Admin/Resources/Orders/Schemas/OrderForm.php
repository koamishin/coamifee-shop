<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\Schemas;

use Filament\Forms\Components\DateTimePicker;
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
                TextInput::make('order_number')
                    ->required(),
                Select::make('customer_id')
                    ->relationship('customer', 'id'),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                TextInput::make('order_type')
                    ->required()
                    ->default('dine_in'),
                TextInput::make('subtotal')
                    ->required()
                    ->numeric(),
                TextInput::make('tax_amount')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('discount_amount')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('total_amount')
                    ->required()
                    ->numeric(),
                Textarea::make('notes')
                    ->columnSpanFull(),
                TextInput::make('table_number'),
                DateTimePicker::make('order_date')
                    ->required(),
                DateTimePicker::make('estimated_ready_time'),
                DateTimePicker::make('completed_at'),
                TextInput::make('created_by')
                    ->required()
                    ->numeric(),
            ]);
    }
}
