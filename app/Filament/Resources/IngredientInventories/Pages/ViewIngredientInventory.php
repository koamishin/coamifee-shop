<?php

declare(strict_types=1);

namespace App\Filament\Resources\IngredientInventories\Pages;

use App\Filament\Resources\IngredientInventories\IngredientInventoryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

final class ViewIngredientInventory extends ViewRecord
{
    protected static string $resource = IngredientInventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
