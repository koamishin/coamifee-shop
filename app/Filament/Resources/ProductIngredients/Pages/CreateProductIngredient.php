<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductIngredients\Pages;

use App\Filament\Resources\ProductIngredients\ProductIngredientResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateProductIngredient extends CreateRecord
{
    protected static string $resource = ProductIngredientResource::class;
}
