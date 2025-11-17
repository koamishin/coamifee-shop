<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductMetrics\Pages;

use App\Filament\Resources\ProductMetrics\ProductMetricResource;
use App\Filament\Resources\ProductMetrics\Widgets\ProductMetricsOverview;
use App\Filament\Resources\ProductMetrics\Widgets\TopPerformingProducts;
use Filament\Resources\Pages\ListRecords;

final class ListProductMetrics extends ListRecords
{
    protected static string $resource = ProductMetricResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ProductMetricsOverview::class,
            TopPerformingProducts::class,
        ];
    }
}
