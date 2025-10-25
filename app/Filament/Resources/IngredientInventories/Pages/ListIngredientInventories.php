<?php

declare(strict_types=1);

namespace App\Filament\Resources\IngredientInventories\Pages;

use App\Filament\Resources\IngredientInventories\IngredientInventoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListIngredientInventories extends ListRecords
{
    protected static string $resource = IngredientInventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
