<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductMetrics\Pages;

use App\Filament\Resources\ProductMetrics\ProductMetricResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

final class EditProductMetric extends EditRecord
{
    protected static string $resource = ProductMetricResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
