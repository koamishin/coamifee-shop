<?php

declare(strict_types=1);

namespace App\Filament\Resources\Ingredients\Pages;

use App\Filament\Resources\Ingredients\IngredientResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

final class ViewIngredient extends ViewRecord
{
    protected static string $resource = IngredientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
