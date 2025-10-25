<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductMetrics\Pages;

use App\Filament\Resources\ProductMetrics\ProductMetricResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateProductMetric extends CreateRecord
{
    protected static string $resource = ProductMetricResource::class;
}
