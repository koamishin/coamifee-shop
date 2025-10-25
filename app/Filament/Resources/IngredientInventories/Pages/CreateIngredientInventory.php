<?php

declare(strict_types=1);

namespace App\Filament\Resources\IngredientInventories\Pages;

use App\Filament\Resources\IngredientInventories\IngredientInventoryResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateIngredientInventory extends CreateRecord
{
    protected static string $resource = IngredientInventoryResource::class;
}
