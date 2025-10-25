<?php

declare(strict_types=1);

namespace App\Filament\Resources\IngredientUsages\Pages;

use App\Filament\Resources\IngredientUsages\IngredientUsageResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

final class ViewIngredientUsage extends ViewRecord
{
    protected static string $resource = IngredientUsageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
