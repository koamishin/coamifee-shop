<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductMetrics\Pages;

use App\Filament\Resources\ProductMetrics\ProductMetricResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

final class ViewProductMetric extends ViewRecord
{
    protected static string $resource = ProductMetricResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
