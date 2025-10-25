<?php

declare(strict_types=1);

namespace App\Filament\Resources\IngredientUsages\Pages;

use App\Filament\Resources\IngredientUsages\IngredientUsageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListIngredientUsages extends ListRecords
{
    protected static string $resource = IngredientUsageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
