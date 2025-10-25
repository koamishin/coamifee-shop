<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductMetrics\Pages;

use App\Filament\Resources\ProductMetrics\ProductMetricResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListProductMetrics extends ListRecords
{
    protected static string $resource = ProductMetricResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
