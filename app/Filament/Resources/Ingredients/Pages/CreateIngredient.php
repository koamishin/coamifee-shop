<?php

declare(strict_types=1);

namespace App\Filament\Resources\Ingredients\Pages;

use App\Filament\Resources\Ingredients\IngredientResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateIngredient extends CreateRecord
{
    protected static string $resource = IngredientResource::class;
}
