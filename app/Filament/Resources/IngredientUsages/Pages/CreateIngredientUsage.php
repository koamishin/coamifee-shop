<?php

declare(strict_types=1);

namespace App\Filament\Resources\IngredientUsages\Pages;

use App\Filament\Resources\IngredientUsages\IngredientUsageResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateIngredientUsage extends CreateRecord
{
    protected static string $resource = IngredientUsageResource::class;
}
