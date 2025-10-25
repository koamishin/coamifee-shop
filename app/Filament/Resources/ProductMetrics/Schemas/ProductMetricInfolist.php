<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductMetrics\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

final class ProductMetricInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('product.name')
                    ->label('Product'),
                TextEntry::make('metric_date')
                    ->date(),
                TextEntry::make('orders_count')
                    ->numeric(),
                TextEntry::make('total_revenue')
                    ->numeric(),
                TextEntry::make('period_type'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
