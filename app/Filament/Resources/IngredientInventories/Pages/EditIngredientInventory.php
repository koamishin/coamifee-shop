<?php

declare(strict_types=1);

namespace App\Filament\Resources\IngredientInventories\Pages;

use App\Filament\Resources\IngredientInventories\IngredientInventoryResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

final class EditIngredientInventory extends EditRecord
{
    protected static string $resource = IngredientInventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
