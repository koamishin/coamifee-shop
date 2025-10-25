<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

final class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('customer_name')
                    ->placeholder('-'),
                TextEntry::make('customer.name')
                    ->label('Customer')
                    ->placeholder('-'),
                TextEntry::make('order_type'),
                TextEntry::make('payment_method'),
                TextEntry::make('total')
                    ->numeric(),
                TextEntry::make('status'),
                TextEntry::make('table_number')
                    ->placeholder('-'),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
